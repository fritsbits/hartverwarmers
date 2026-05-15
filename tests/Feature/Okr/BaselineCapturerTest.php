<?php

namespace Tests\Feature\Okr;

use App\Models\Okr\Initiative;
use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use App\Models\User;
use App\Services\Okr\BaselineCapturer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaselineCapturerTest extends TestCase
{
    use RefreshDatabase;

    public function test_captures_one_baseline_row_per_parent_objective_kr(): void
    {
        $objective = Objective::factory()->create();
        $kr1 = KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'onboarding_signup_count',
        ]);
        $kr2 = KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'onboarding_verification_rate',
        ]);

        User::factory()->create([
            'created_at' => '2026-04-01',
            'email_verified_at' => '2026-04-02',
            'role' => 'contributor',
        ]);

        $initiative = Initiative::factory()->create([
            'objective_id' => $objective->id,
            'started_at' => '2026-04-15',
        ]);

        app(BaselineCapturer::class)->captureFor($initiative);

        $this->assertCount(2, $initiative->fresh()->baselines);

        $b1 = $initiative->baselines->firstWhere('key_result_id', $kr1->id);
        $this->assertSame(1.0, (float) $b1->baseline_value);

        $b2 = $initiative->baselines->firstWhere('key_result_id', $kr2->id);
        $this->assertSame(100.0, (float) $b2->baseline_value);
        $this->assertSame('%', $b2->baseline_unit);
    }

    public function test_is_idempotent_does_not_duplicate_baselines(): void
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

        app(BaselineCapturer::class)->captureFor($initiative);
        app(BaselineCapturer::class)->captureFor($initiative);

        $this->assertCount(1, $initiative->fresh()->baselines);
    }

    public function test_writes_null_baseline_for_kr_without_metric_key(): void
    {
        $objective = Objective::factory()->create();
        $kr = KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => null,
        ]);
        $initiative = Initiative::factory()->create([
            'objective_id' => $objective->id,
            'started_at' => '2026-04-15',
        ]);

        app(BaselineCapturer::class)->captureFor($initiative);

        $baseline = $initiative->fresh()->baselines->firstWhere('key_result_id', $kr->id);
        $this->assertNotNull($baseline);
        $this->assertNull($baseline->baseline_value);
    }

    public function test_does_nothing_when_initiative_has_no_started_at(): void
    {
        $objective = Objective::factory()->create();
        KeyResult::factory()->create(['objective_id' => $objective->id, 'metric_key' => 'onboarding_signup_count']);
        $initiative = Initiative::factory()->create(['objective_id' => $objective->id, 'started_at' => null]);

        app(BaselineCapturer::class)->captureFor($initiative);

        $this->assertCount(0, $initiative->fresh()->baselines);
    }
}
