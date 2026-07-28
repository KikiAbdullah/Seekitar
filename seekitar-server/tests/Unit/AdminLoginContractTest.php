<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Kontrak login panel admin.
 *
 * Login admin punya banyak titik gagal yang SEMUANYA senyap: akun terbuat
 * tapi tanpa sandi, sandi tersimpan plaintext, atau strict mode melempar
 * saat membaca kolom yang tidak ter-SELECT. Tidak satu pun memunculkan
 * error saat deploy — barunya ketahuan ketika admin mencoba masuk.
 */
class AdminLoginContractTest extends TestCase
{
    private function sourceOf(string $class): string
    {
        return file_get_contents((new ReflectionClass($class))->getFileName());
    }

    private function seederSource(): string
    {
        return file_get_contents(__DIR__.'/../../database/seeders/RolesAndPermissionsSeeder.php');
    }

    public function test_seeder_menyetel_email_dan_kata_sandi(): void
    {
        $src = $this->seederSource();

        // Tanpa keduanya, Auth::attempt() mustahil berhasil dan akun
        // super-admin terbuat tetapi tidak bisa masuk.
        $this->assertStringContainsString('super_admin_email', $src);
        $this->assertStringContainsString('super_admin_password', $src);
    }

    public function test_seeder_menyimpan_perubahan(): void
    {
        // Menetapkan properti tanpa save() hanya mengubah objek di memori.
        $this->assertMatchesRegularExpression(
            '/isDirty\(\)\s*\)\s*\{\s*\$user->save\(\);/s',
            $this->seederSource(),
            'Seeder tidak memanggil save() — kredensial tidak pernah tersimpan.',
        );
    }

    /**
     * Model::shouldBeStrict() aktif di SEMUA environment non-produksi, dan
     * membaca properti yang tidak ikut ter-SELECT melempar
     * MissingAttributeException. Membacanya lewat getAttributes() aman
     * apa pun bentuk query-nya.
     */
    public function test_pembacaan_password_aman_dari_strict_mode(): void
    {
        foreach ([$this->seederSource(), $this->sourceOf(User::class)] as $src) {
            $this->assertStringContainsString(
                "getAttributes()['password']",
                $src,
                'Password dibaca lewat properti — akan melempar saat kolomnya tidak ter-SELECT.',
            );
        }
    }

    public function test_kata_sandi_lama_tidak_ditimpa_saat_deploy_ulang(): void
    {
        // Menimpa setiap deploy mengembalikan sandi yang sudah diganti admin
        // ke nilai default — dan nilai itu ada di .env.example.
        $this->assertMatchesRegularExpression(
            '/wasRecentlyCreated \|\| \$existingPassword === null/',
            $this->seederSource(),
        );
    }

    public function test_kata_sandi_tidak_pernah_disimpan_plaintext(): void
    {
        $src = $this->sourceOf(User::class);

        $this->assertMatchesRegularExpression(
            "/'password'\s*=>\s*'hashed'/",
            $src,
            "Tanpa cast 'hashed', sandi bisa tersimpan sebagai teks biasa.",
        );
    }

    public function test_kata_sandi_disembunyikan_dari_serialisasi(): void
    {
        $hidden = (new User())->getHidden();

        $this->assertContains('password', $hidden);
        $this->assertContains('remember_token', $hidden);
    }

    public function test_login_menolak_akun_tanpa_peran_admin(): void
    {
        $src = $this->sourceOf(\App\Http\Controllers\Admin\LoginController::class);

        // Pemeriksaan peran harus SETELAH kredensial benar, dan sesinya
        // dibuang bila gagal — kalau tidak, pengguna biasa yang punya sandi
        // tetap masuk ke panel.
        $this->assertStringContainsString('canAccessAdminPanel()', $src);
        $this->assertStringContainsString('Auth::logout()', $src);

        // Session fixation: id sesi sebelum login harus diganti sesudahnya.
        $this->assertStringContainsString('session()->regenerate()', $src);
    }

    public function test_pesan_gagal_login_tidak_membocorkan_email_terdaftar(): void
    {
        $src = $this->sourceOf(\App\Http\Controllers\Admin\LoginController::class);

        // Satu pesan untuk kedua kasus; membedakannya memberi tahu penyerang
        // email mana yang ada di sistem (enumerasi akun).
        $this->assertSame(
            1,
            substr_count($src, 'Email atau kata sandi salah.'),
        );
    }
}
