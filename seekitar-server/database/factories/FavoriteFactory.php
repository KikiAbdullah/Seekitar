<?php

namespace Database\Factories;

use App\Models\Favorite;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Favorite>
 *
 * UNIQUE (user_id, listing_id): satu pengguna hanya bisa memfavoritkan satu
 * listing sekali. Inilah yang membuat POST /listings/{id}/favorite bersifat
 * idempoten seperti dijanjikan API §4.4.
 *
 * Karena itu seeder yang memakai factory ini WAJIB memasangkan user & listing
 * secara unik — mengacak keduanya pada ratusan baris pasti menabrak UNIQUE.
 * Lihat FavoriteSeeder yang memakai kombinasi terkontrol.
 */
class FavoriteFactory extends Factory
{
    protected $model = Favorite::class;

    public function definition(): array
    {
        return [
            'id'         => (string) Str::uuid7(),
            'user_id'    => User::factory(),
            'listing_id' => Listing::factory(),
            'created_at' => now()->subDays(fake()->numberBetween(0, 90)),
        ];
    }
}
