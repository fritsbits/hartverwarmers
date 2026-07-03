<?php

namespace Database\Factories;

use App\Models\DiamondRotation;
use App\Models\Fiche;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiamondRotation>
 */
class DiamondRotationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'month' => now()->startOfMonth()->toDateString(),
            'fiche_id' => Fiche::factory()->published(),
            'suggested_fiche_ids' => [],
            'chosen_via' => 'auto',
            'suggestion_sent_at' => now()->subDays(3),
            'awarded_at' => null,
        ];
    }

    public function awarded(): static
    {
        return $this->state(fn () => ['awarded_at' => now()]);
    }
}
