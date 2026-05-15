<?php

namespace Tests\Feature\Okr;

use App\Models\Okr\Initiative;
use App\Models\Okr\InitiativeBaseline;
use App\Models\Okr\KeyResult;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitiativeBaselineTest extends TestCase
{
    use RefreshDatabase;

    public function test_baseline_belongs_to_initiative_and_key_result(): void
    {
        $kr = KeyResult::factory()->create();
        $initiative = Initiative::factory()->create(['objective_id' => $kr->objective_id]);

        $baseline = InitiativeBaseline::factory()->create([
            'initiative_id' => $initiative->id,
            'key_result_id' => $kr->id,
            'baseline_value' => 42,
            'baseline_unit' => '',
        ]);

        $this->assertSame($initiative->id, $baseline->initiative->id);
        $this->assertSame($kr->id, $baseline->keyResult->id);
        $this->assertSame(42.0, (float) $baseline->baseline_value);
    }

    public function test_initiative_has_many_baselines(): void
    {
        $initiative = Initiative::factory()->create();
        $kr = KeyResult::factory()->create(['objective_id' => $initiative->objective_id]);
        InitiativeBaseline::factory()->create([
            'initiative_id' => $initiative->id,
            'key_result_id' => $kr->id,
        ]);

        $this->assertCount(1, $initiative->fresh()->baselines);
    }

    public function test_unique_pair_of_initiative_and_key_result(): void
    {
        $initiative = Initiative::factory()->create();
        $kr = KeyResult::factory()->create(['objective_id' => $initiative->objective_id]);

        InitiativeBaseline::factory()->create([
            'initiative_id' => $initiative->id,
            'key_result_id' => $kr->id,
        ]);

        $this->expectException(QueryException::class);

        InitiativeBaseline::factory()->create([
            'initiative_id' => $initiative->id,
            'key_result_id' => $kr->id,
        ]);
    }
}
