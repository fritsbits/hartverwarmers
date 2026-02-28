<?php

namespace Database\Factories;

use App\Models\Fiche;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Like>
 */
class LikeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'likeable_type' => Fiche::class,
            'likeable_id' => Fiche::factory(),
            'type' => 'like',
        ];
    }

    public function bookmark(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'bookmark',
        ]);
    }

    public function kudos(int $count = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'kudos',
            'count' => $count,
        ]);
    }
}
