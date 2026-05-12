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
        return [
            'theme_id' => Theme::factory(),
            'year' => 2026,
            'start_date' => '2026-06-21',
            'end_date' => null,
        ];
    }
}
