<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserDevice>
 *
 * `device_id` UNIQUE pada kolomnya SENDIRI — bukan bersama user_id. Satu
 * ponsel hanya boleh terikat ke satu akun; kalau tidak, notifikasi pemilik
 * lama tetap masuk ke ponsel bekas (DATABASE.md §4.9a).
 */
class UserDeviceFactory extends Factory
{
    protected $model = UserDevice::class;

    public function definition(): array
    {
        return [
            'id'      => (string) Str::uuid7(),
            'user_id' => User::factory(),

            // UUID, bukan numerify: nilai acak pendek mudah bentrok pada
            // ratusan baris dan UNIQUE-nya akan gagal di tengah seeding.
            'device_id' => (string) Str::uuid7(),

            // Panjang token FCM asli ~152–163 karakter; kolomnya varchar(255).
            'fcm_token' => Str::random(152),

            'platform'     => fake()->randomElement(['android', 'ios']),
            'last_used_at' => now()->subMinutes(fake()->numberBetween(1, 20160)),
        ];
    }

    public function android(): static
    {
        return $this->state(['platform' => 'android']);
    }

    public function ios(): static
    {
        return $this->state(['platform' => 'ios']);
    }

    /** Perangkat lama yang sudah lama tidak dipakai. */
    public function tidakAktif(): static
    {
        return $this->state(fn () => [
            'last_used_at' => now()->subDays(fake()->numberBetween(90, 400)),
        ]);
    }
}
