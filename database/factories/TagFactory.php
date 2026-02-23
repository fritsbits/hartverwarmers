<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => fake()->randomElement(['interest', 'guidance', 'goal']),
        ];
    }

    public function interest(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'interest',
        ]);
    }

    public function guidance(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'guidance',
        ]);
    }

    public function goal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'goal',
        ]);
    }
}
