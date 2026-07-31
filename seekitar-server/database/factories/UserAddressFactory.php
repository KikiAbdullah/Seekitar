<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserAddressFactory extends Factory
{
    protected $model = UserAddress::class;

    public function definition(): array
    {
        return [
            'id'              => (string) Str::uuid7(),
            'user_id'         => User::factory(),
            'label'           => fake()->randomElement(['Rumah', 'Kantor', 'Kost', 'Tempat Usaha']),
            'address'         => fake('id_ID')->streetAddress() . ', ' . fake('id_ID')->city(),
            'recipient_name'  => fake('id_ID')->name(),
            'recipient_phone' => '628' . fake()->numerify('##########'),
            'is_default'      => false,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
