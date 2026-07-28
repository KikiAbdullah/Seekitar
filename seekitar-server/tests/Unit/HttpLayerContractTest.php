<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureProfileComplete;
use App\Http\Middleware\EnsureStoreOwner;
use App\Http\Requests\Api\RequestOtpRequest;
use App\Http\Requests\Api\StoreListingRequest;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Http\Requests\Api\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\Concerns\SerializesDatesAsUtc;
use App\Models\Order;
use App\Models\User;
use App\Support\PhoneNumber;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Kontrak lapisan HTTP yang bisa diperiksa TANPA basis data.
 *
 * Alur end-to-end (request → respons) tetap butuh MySQL dan diuji terpisah
 * sebagai Feature test. Yang dikunci di sini adalah janji-janji struktural
 * yang mudah rusak diam-diam saat kode dirapikan.
 */
class HttpLayerContractTest extends TestCase
{
    /** Aturan validasi diambil tanpa membuat instance Request penuh. */
    private function rulesOf(string $class): array
    {
        $request = new $class();

        return $request->rules();
    }

    public function test_form_request_otp_menormalkan_nomor_sebelum_validasi(): void
    {
        // prepareForValidation() adalah SATU-SATUNYA tempat normalisasi
        // terjadi pada alur HTTP. Kalau hilang, 08xxx dan 62xxx menjadi dua
        // akun berbeda meski UNIQUE(users.phone) terpasang.
        foreach ([RequestOtpRequest::class, VerifyOtpRequest::class] as $class) {
            $method = new ReflectionMethod($class, 'prepareForValidation');

            $this->assertTrue(
                $method->isProtected(),
                "$class harus punya prepareForValidation()",
            );

            $source = file_get_contents((new ReflectionClass($class))->getFileName());
            $this->assertStringContainsString(
                'PhoneNumber::normalize',
                $source,
                "$class tidak menormalkan nomor telepon",
            );
        }
    }

    public function test_validasi_listing_mencerminkan_check_constraint(): void
    {
        $rules = $this->rulesOf(StoreListingRequest::class);

        // Cermin dari listings_qty_slot_chk: jasa tidak boleh punya stok,
        // produk tidak boleh punya slot (DATABASE.md §4.4).
        $this->assertContains('required_if:listing_type,product,rental', $rules['stock_qty']);
        $this->assertContains('prohibited_unless:listing_type,product,rental', $rules['stock_qty']);
        $this->assertContains('required_if:listing_type,service', $rules['slot']);
        $this->assertContains('prohibited_unless:listing_type,service', $rules['slot']);
    }

    public function test_pesanan_menolak_offer_id_dari_endpoint_katalog(): void
    {
        $rules = $this->rulesOf(StoreOrderRequest::class);

        // Pesanan dari penawaran dibuat di dalam transaksi accept-offer.
        // Mengirim offer_id ke POST /orders harus selalu 422 (API §7.1).
        $this->assertContains('prohibits:offer_id', $rules['listing_id']);

        // Cermin dari orders_shipping_chk.
        $this->assertContains('required_if:delivery_method,delivery', $rules['shipping_address']);
    }

    public function test_timestamp_diserialisasi_sebagai_utc_tanpa_mikrodetik(): void
    {
        $order = new Order();
        // setAccessible() tidak lagi diperlukan sejak PHP 8.1 dan sudah
        // deprecated di 8.5 — method protected bisa langsung di-invoke.
        $method = new ReflectionMethod($order, 'serializeDate');

        $formatted = $method->invoke($order, new DateTimeImmutable('2026-07-28T03:00:00+07:00'));

        // Kontrak API §12.1: ISO 8601 UTC berakhiran Z, tanpa mikrodetik.
        $this->assertSame('2026-07-27T20:00:00Z', $formatted);
        $this->assertStringNotContainsString('.', $formatted);
    }

    public function test_semua_model_memakai_serialisasi_tanggal_seragam(): void
    {
        $models = glob(__DIR__.'/../../app/Models/*.php');

        $this->assertNotEmpty($models);

        foreach ($models as $file) {
            $class = 'App\\Models\\'.basename($file, '.php');

            $this->assertContains(
                SerializesDatesAsUtc::class,
                class_uses_recursive($class),
                "$class tidak memakai SerializesDatesAsUtc — tanggalnya akan beda format.",
            );
        }
    }

    public function test_resource_user_tidak_membocorkan_data_sensitif(): void
    {
        $source = file_get_contents((new ReflectionClass(UserResource::class))->getFileName());

        // NIK & kolom pemblokiran adalah data pribadi (UU PDP); keduanya
        // tidak boleh ikut keluar lewat resource publik.
        foreach (['nik', 'nik_hash', 'blocked_reason', 'ktp_image', 'selfie_image'] as $sensitive) {
            $this->assertStringNotContainsString(
                "'{$sensitive}'",
                $source,
                "UserResource membocorkan kolom sensitif: {$sensitive}",
            );
        }
    }

    public function test_middleware_kepemilikan_toko_membalas_404_bukan_403(): void
    {
        $source = file_get_contents((new ReflectionClass(EnsureStoreOwner::class))->getFileName());

        // 403 memberi tahu penyerang bahwa id itu ADA. Bagi bukan-pemilik,
        // toko orang lain memang seolah tidak ada.
        //
        // Yang diperiksa adalah KODE STATUS yang benar-benar dikembalikan,
        // bukan sekadar keberadaan angkanya: komentar di kelas itu justru
        // menjelaskan kenapa 403 dihindari.
        $this->assertMatchesRegularExpression('/\], 404\);/', $source);
        $this->assertDoesNotMatchRegularExpression('/\], 403\);/', $source);
    }

    public function test_middleware_profil_memakai_isProfileComplete(): void
    {
        $source = file_get_contents((new ReflectionClass(EnsureProfileComplete::class))->getFileName());

        // Memeriksa kolom langsung pernah menjadi bug: `latitude` sudah tidak
        // ada sejak skema memakai POINT, sehingga pemeriksaan selalu gagal.
        $this->assertStringContainsString('isProfileComplete()', $source);
        $this->assertStringNotContainsString('latitude', $source);
    }

    public function test_nomor_telepon_disensor_untuk_log(): void
    {
        $this->assertSame('+62 812-****-789', PhoneNumber::mask('08123456789'));
    }

    public function test_user_model_menjanjikan_profil_lengkap(): void
    {
        $this->assertTrue(method_exists(User::class, 'isProfileComplete'));
        $this->assertTrue(method_exists(User::class, 'displayName'));
    }
}
