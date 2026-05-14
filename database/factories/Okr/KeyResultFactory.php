<?php

namespace Database\Factories\Okr;

use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KeyResult>
 */
class KeyResultFactory extends Factory
{
    protected $model = KeyResult::class;

    public function definition(): array
    {
        return [
            'objective_id' => Objective::factory(),
            'label' => fake()->sentence(3),
            'metric_key' => null,
            'target_value' => null,
            'target_unit' => '',
            'position' => 0,
        ];
    }
}
