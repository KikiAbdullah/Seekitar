<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'id'      => (string) Str::uuid7(),
            'user_id' => User::factory(),
            'type'    => fake()->randomElement(['info', 'warning', 'success', 'order', 'offer']),
            'title'   => fake()->sentence(4),
            'body'    => fake()->optional()->sentence(10),
            'data'    => null,
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn () => [
            'read_at' => now()->subHours(fake()->numberBetween(1, 48)),
        ]);
    }

    public function unread(): static
    {
        return $this->state(['read_at' => null]);
    }
}
