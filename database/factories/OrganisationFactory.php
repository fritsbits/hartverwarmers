<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organisation>
 */
class OrganisationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'city' => fake()->city(),
        ];
    }
}
