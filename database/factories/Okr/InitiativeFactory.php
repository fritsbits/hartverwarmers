<?php

namespace Database\Factories\Okr;

use App\Models\Okr\Initiative;
use App\Models\Okr\Objective;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Initiative>
 */
class InitiativeFactory extends Factory
{
    protected $model = Initiative::class;

    public function definition(): array
    {
        $label = fake()->unique()->words(2, true);

        return [
            'objective_id' => Objective::factory(),
            'slug' => Str::slug($label),
            'label' => ucfirst($label),
            'status' => 'in_progress',
            'description' => null,
            'position' => 0,
        ];
    }
}
