<?php

namespace Tests\Feature\Okr;

use App\Models\Okr\Initiative;
use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitiativeBaselineHookTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_with_started_at_captures_baselines(): void
    {
        $objective = Objective::factory()->create();
        KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'onboarding_signup_count',
        ]);

        $initiative = Initiative::create([
            'objective_id' => $objective->id,
            'slug' => 'test-init',
            'label' => 'Test',
            'status' => 'in_progress',
            'started_at' => '2026-04-15',
            'position' => 1,
        ]);

        $this->assertCount(1, $initiative->fresh()->baselines);
    }

    public function test_saving_without_started_at_does_not_capture(): void
    {
        $objective = Objective::factory()->create();
        KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'onboarding_signup_count',
        ]);

        $initiative = Initiative::create([
            'objective_id' => $objective->id,
            'slug' => 'test-init',
            'label' => 'Test',
            'status' => 'in_progress',
            'started_at' => null,
            'position' => 1,
        ]);

        $this->assertCount(0, $initiative->fresh()->baselines);
    }

    public function test_setting_started_at_on_update_captures_baselines(): void
    {
        $objective = Objective::factory()->create();
        KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'onboarding_signup_count',
        ]);

        $initiative = Initiative::factory()->create([
            'objective_id' => $objective->id,
            'started_at' => null,
        ]);

        $initiative->update(['started_at' => '2026-04-15']);

        $this->assertCount(1, $initiative->fresh()->baselines);
    }

    public function test_updating_other_fields_does_not_rewrite_baselines(): void
    {
        $objective = Objective::factory()->create();
        KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'onboarding_signup_count',
        ]);

        $initiative = Initiative::factory()->create([
            'objective_id' => $objective->id,
            'started_at' => '2026-04-15',
        ]);

        $originalBaselineAt = $initiative->fresh()->baselines->first()->baseline_at;

        $initiative->update(['label' => 'Renamed']);

        $this->assertEquals(
            $originalBaselineAt->toDateTimeString(),
            $initiative->fresh()->baselines->first()->baseline_at->toDateTimeString(),
        );
        $this->assertCount(1, $initiative->fresh()->baselines);
    }
}
