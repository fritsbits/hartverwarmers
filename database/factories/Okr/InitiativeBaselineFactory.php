<?php

namespace Database\Factories\Okr;

use App\Models\Okr\Initiative;
use App\Models\Okr\InitiativeBaseline;
use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InitiativeBaseline>
 */
class InitiativeBaselineFactory extends Factory
{
    protected $model = InitiativeBaseline::class;

    public function definition(): array
    {
        $objective = Objective::factory()->create();

        return [
            'initiative_id' => Initiative::factory()->for($objective, 'objective'),
            'key_result_id' => KeyResult::factory()->for($objective, 'objective'),
            'baseline_value' => fake()->randomFloat(2, 0, 100),
            'baseline_unit' => '',
            'baseline_at' => now(),
            'low_data' => false,
        ];
    }
}
