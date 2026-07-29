<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 *
 * FACTORY BAWAAN LARAVEL TIDAK BISA DIPAKAI DI SEEKITAR.
 * ------------------------------------------------------
 * Versi bawaan menghasilkan `name`, `email`, `email_verified_at`, `password`,
 * `remember_token` — dan TIDAK PERNAH mengisi `phone`. Padahal `users.phone`
 * bersifat NOT NULL + UNIQUE dan merupakan identitas akun di Seekitar
 * (DATABASE.md §4.1). Setiap `User::factory()->create()` akan gagal dengan
 * "Field 'phone' doesn't have a default value".
 *
 * Email & kata sandi justru NULL secara default: hanya akun admin yang
 * memilikinya. Pengguna aplikasi masuk lewat OTP WhatsApp.
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Penghitung nomor telepon.
     *
     * Faker `phoneNumber()` id_ID menghasilkan format lokal ("0751 4862 879")
     * yang tidak sesuai kolom `phone` — E.164 tanpa plus, maks 15 karakter.
     * `unique()->numerify()` pun masih bisa bentrok lintas-proses, jadi
     * dipakai penghitung yang dijamin naik.
     */
    private static int $phoneSeq = 0;

    public function definition(): array
    {
        // 62 + 10 digit = 12 karakter, aman di kolom varchar(15).
        $phone = '628' . str_pad((string) (++self::$phoneSeq), 9, '0', STR_PAD_LEFT);

        return [
            'id'     => (string) Str::uuid7(),
            'phone'  => $phone,
            'name'   => fake('id_ID')->name(),
            // 'menunggu' juga default kolomnya — ditulis eksplisit supaya
            // kontrak kedudukan akun terbaca di sini, bukan di DDL saja.
            'status' => \App\Enums\UserStatus::Menunggu,
        ];
    }

    /** Belum pernah mengirim berkas — belum boleh membuka toko (default). */
    public function basic(): static
    {
        return $this->state(fn () => [
            'status'     => \App\Enums\UserStatus::Menunggu,
            'verified_at' => null,
        ]);
    }

    /**
     * Identitas disetujui admin: syarat minimum membuka toko (PRD §5.3.2).
     * "Level 2" kontrak API tidak ditulis ke mana-mana — ia turunan dari
     * stempel ini.
     */
    public function verified(): static
    {
        return $this->state(fn () => [
            'status'           => \App\Enums\UserStatus::Terverifikasi,
            'ktp_submitted_at' => now()->subDays(fake()->numberBetween(3, 60)),
            'verified_at'      => now()->subDays(fake()->numberBetween(1, 58)),
            'address'          => fake('id_ID')->streetAddress(),
        ]);
    }

    /**
     * Alias verified() untuk niat "penjual matang".
     *
     * Level 3 TIDAK bisa di-set dari factory pengguna: Usaha Terverifikasi
     * adalah turunan dari toko berstatus verified — beri pemilik ini toko
     * terverifikasi dan lencana Pro muncul dengan sendirinya.
     */
    public function pro(): static
    {
        return $this->verified()->state(fn () => [
            'ktp_submitted_at' => now()->subDays(fake()->numberBetween(30, 200)),
        ]);
    }

    /**
     * Menunggu tinjauan: berkas SUDAH dikirim dan kedudukannya `menunggu`.
     *
     * Inilah definisi antrian verifikasi di VerificationController
     * (scope pendingVerification) — bukan sekadar "punya ktp_image".
     */
    public function menungguKtp(): static
    {
        return $this->state(fn () => [
            'status'           => \App\Enums\UserStatus::Menunggu,
            'ktp_submitted_at' => now()->subHours(fake()->numberBetween(1, 40)),
            'ktp_image'        => 'ktp/'.Str::uuid7().'.jpg',
            'selfie_image'     => 'selfie/'.Str::uuid7().'.jpg',
        ]);
    }

    public function diblokir(string $alasan = 'Melanggar ketentuan layanan.'): static
    {
        return $this->state(fn () => [
            'status'         => \App\Enums\UserStatus::Diblokir,
            'blocked_reason' => $alasan,
            'blocked_at'     => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }
}
