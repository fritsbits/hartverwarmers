<?php

namespace Database\Factories\Okr;

use App\Models\Okr\Objective;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Objective>
 */
class ObjectiveFactory extends Factory
{
    protected $model = Objective::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($title),
            'title' => ucfirst($title),
            'description' => fake()->optional()->sentence(),
            'status' => 'on_track',
            'position' => 0,
        ];
    }
}
