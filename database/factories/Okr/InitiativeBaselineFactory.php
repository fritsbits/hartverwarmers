<?php

namespace Database\Factories\Okr;

use App\Models\Okr\Initiative;
use App\Models\Okr\InitiativeBaseline;
use App\Models\Okr\KeyResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InitiativeBaseline>
 */
class InitiativeBaselineFactory extends Factory
{
    protected $model = InitiativeBaseline::class;

    public function definition(): array
    {
        return [
            'initiative_id' => Initiative::factory(),
            'key_result_id' => KeyResult::factory(),
            'baseline_value' => fake()->numberBetween(0, 100),
            'baseline_unit' => '',
            'baseline_at' => now(),
            'low_data' => false,
        ];
    }
}
