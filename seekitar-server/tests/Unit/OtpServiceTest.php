<?php

namespace Tests\Unit;

use App\Services\OtpService;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Hashing\HashManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\TestCase;

/**
 * Jaminan keamanan OtpService (§15.2): di-hash, TTL 5 menit, batas 5 percobaan.
 *
 * Memakai ArrayStore, bukan Redis — perilakunya identik untuk kontrak cache
 * yang dipakai, dan test jadi bisa jalan tanpa server apa pun.
 */
class OtpServiceTest extends TestCase
{
    private Repository $cache;
    private OtpService $otp;

    protected function setUp(): void
    {
        parent::setUp();

        // Hash facade butuh container; bcrypt cost diturunkan agar test cepat.
        $container = new Container();
        $container->singleton('config', fn () => new \Illuminate\Config\Repository([
            'hashing' => ['driver' => 'bcrypt', 'bcrypt' => ['rounds' => 4]],
        ]));
        $container->singleton('hash', fn ($app) => new HashManager($app));
        Container::setInstance($container);
        Facade::setFacadeApplication($container);

        $this->cache = new Repository(new ArrayStore());
        $this->otp   = new OtpService($this->cache);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_kode_disimpan_sebagai_hash_bukan_plaintext(): void
    {
        $code = $this->otp->generate('08123456789');

        $stored = $this->cache->get('otp:628123456789');

        $this->assertNotSame($code, $stored, 'OTP tersimpan plaintext — dump Redis akan membocorkannya');
        $this->assertTrue(Hash::check($code, $stored));
    }

    public function test_kode_enam_digit(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $code = $this->otp->generate("0812000{$i}");

            $this->assertSame(6, strlen($code));
            $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        }
    }

    public function test_kunci_cache_memakai_nomor_ternormalisasi(): void
    {
        // Diminta sebagai 08..., diverifikasi sebagai +62... — nomor yang sama.
        $code = $this->otp->generate('08123456789');

        $this->assertTrue($this->otp->verify('+62 812-3456-789', $code));
    }

    public function test_kode_hanya_bisa_dipakai_sekali(): void
    {
        $code = $this->otp->generate('08123456789');

        $this->assertTrue($this->otp->verify('08123456789', $code));
        $this->assertFalse($this->otp->verify('08123456789', $code), 'kode berhasil dipakai dua kali');
    }

    public function test_kode_salah_ditolak(): void
    {
        $this->otp->generate('08123456789');

        $this->assertFalse($this->otp->verify('08123456789', '000000'));
    }

    public function test_batas_lima_percobaan(): void
    {
        $code = $this->otp->generate('08123456789');

        $this->assertSame(OtpService::MAX_ATTEMPTS, $this->otp->attemptsLeft('08123456789'));

        for ($i = 0; $i < OtpService::MAX_ATTEMPTS; $i++) {
            $this->otp->verify('08123456789', '000000');
        }

        $this->assertSame(0, $this->otp->attemptsLeft('08123456789'));

        // Kode 6 digit hanya punya 1.000.000 kemungkinan; tanpa batas ini,
        // seluruhnya bisa dicoba dalam masa berlaku 5 menit.
        $this->assertFalse(
            $this->otp->verify('08123456789', $code),
            'kode benar masih diterima setelah percobaan habis',
        );
    }

    public function test_kedaluwarsa_lima_menit(): void
    {
        $code = $this->otp->generate('08123456789');

        $this->assertTrue($this->otp->hasActiveCode('08123456789'));

        Carbon::setTestNow(now()->addSeconds(OtpService::TTL_SECONDS + 1));

        $this->assertFalse($this->otp->hasActiveCode('08123456789'));
        $this->assertFalse($this->otp->verify('08123456789', $code));
    }

    public function test_ttl_sesuai_dokumen(): void
    {
        $this->assertSame(300, OtpService::TTL_SECONDS);   // §15.2: 5 menit
        $this->assertSame(5, OtpService::MAX_ATTEMPTS);
    }

    public function test_verifikasi_tanpa_kode_aktif_ditolak(): void
    {
        $this->assertFalse($this->otp->verify('08999999999', '123456'));
    }

    public function test_forget_membuang_kode_dan_hitungan(): void
    {
        $code = $this->otp->generate('08123456789');
        $this->otp->forget('08123456789');

        $this->assertFalse($this->otp->hasActiveCode('08123456789'));
        $this->assertFalse($this->otp->verify('08123456789', $code));
    }
}
