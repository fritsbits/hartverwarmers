<?php

namespace Database\Factories;

use App\Models\Interest;
use Illuminate\Database\Eloquent\Factories\Factory;

class InterestFactory extends Factory
{
    protected $model = Interest::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'type' => 'interest',
            'interest_category_id' => null,
            'parent_id' => null,
        ];
    }

    public function domain(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'domain',
        ]);
    }

    public function interest(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'interest',
        ]);
    }
}
