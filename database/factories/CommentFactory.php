<?php

namespace Database\Factories;

use App\Models\Fiche;
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
            'commentable_type' => Fiche::class,
            'commentable_id' => Fiche::factory(),
            'body' => fake()->paragraph(),
        ];
    }
}
