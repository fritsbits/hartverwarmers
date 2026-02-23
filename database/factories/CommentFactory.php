<?php

namespace Database\Factories;

use App\Models\Elaboration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'commentable_type' => Elaboration::class,
            'commentable_id' => Elaboration::factory(),
            'body' => fake()->paragraph(),
        ];
    }
}
