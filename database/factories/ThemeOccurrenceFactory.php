<?php

namespace Database\Factories;

use App\Models\Theme;
use App\Models\ThemeOccurrence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThemeOccurrence>
 */
class ThemeOccurrenceFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('2020-01-01', '2030-12-31');

        return [
            'theme_id' => Theme::factory(),
            'year' => (int) $start->format('Y'),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => null,
        ];
    }
}
