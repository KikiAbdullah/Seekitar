<?php

namespace Database\Factories;

use App\Enums\VerificationLevel;
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
            'id'                 => (string) Str::uuid7(),
            'phone'              => $phone,
            'name'               => fake('id_ID')->name(),
            'verification_level' => VerificationLevel::Basic,
            'is_blocked'         => false,
        ];
    }

    /** Nomor HP terverifikasi saja — belum boleh membuka toko. */
    public function basic(): static
    {
        return $this->state(['verification_level' => VerificationLevel::Basic]);
    }

    /** KTP disetujui: syarat minimum membuka toko (PRD §5.3.2). */
    public function verified(): static
    {
        return $this->state(fn () => [
            'verification_level' => VerificationLevel::Verified,
            'ktp_submitted_at'   => now()->subDays(fake()->numberBetween(3, 60)),
            'address'            => fake('id_ID')->streetAddress(),
        ]);
    }

    /** Usaha tervalidasi — prioritas siaran lebih tinggi. */
    public function pro(): static
    {
        return $this->state(fn () => [
            'verification_level' => VerificationLevel::Pro,
            'ktp_submitted_at'   => now()->subDays(fake()->numberBetween(30, 200)),
            'address'            => fake('id_ID')->streetAddress(),
        ]);
    }

    /**
     * Menunggu peninjauan KTP: berkas SUDAH dikirim tapi level masih Basic.
     *
     * Inilah definisi antrian verifikasi di VerificationController — bukan
     * sekadar "punya ktp_image".
     */
    public function menungguKtp(): static
    {
        return $this->state(fn () => [
            'verification_level' => VerificationLevel::Basic,
            'ktp_submitted_at'   => now()->subHours(fake()->numberBetween(1, 40)),
            'ktp_image'          => 'ktp/'.Str::uuid7().'.jpg',
            'selfie_image'       => 'selfie/'.Str::uuid7().'.jpg',
        ]);
    }

    public function diblokir(string $alasan = 'Melanggar ketentuan layanan.'): static
    {
        return $this->state(fn () => [
            'is_blocked'     => true,
            'blocked_reason' => $alasan,
            'blocked_at'     => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }
}
