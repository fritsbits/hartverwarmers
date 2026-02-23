<?php

namespace Database\Factories;

use App\Models\Elaboration;
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
            'likeable_type' => Elaboration::class,
            'likeable_id' => Elaboration::factory(),
            'type' => 'like',
        ];
    }

    public function bookmark(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'bookmark',
        ]);
    }
}
