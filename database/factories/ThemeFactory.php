<?php

namespace Database\Factories;

use App\Enums\ThemeRecurrenceRule;
use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Theme>
 */
class ThemeFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'title' => ucfirst($title),
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'description' => fake()->sentence(),
            'is_month' => false,
            'recurrence_rule' => ThemeRecurrenceRule::Fixed,
            'recurrence_detail' => 'Fixed: month-day 06-21',
        ];
    }

    public function season(): static
    {
        return $this->state(fn () => ['is_month' => true]);
    }
}
