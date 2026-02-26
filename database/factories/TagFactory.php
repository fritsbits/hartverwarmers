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
            'type' => fake()->randomElement(['theme', 'guidance', 'goal']),
        ];
    }

    public function theme(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'theme',
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
