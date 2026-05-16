<?php

namespace Tests\Feature\Okr;

use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use App\Models\User;
use App\Services\Okr\MetricRegistry;
use App\Services\Okr\ObjectiveStatBuilder;
use Database\Seeders\OkrSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ObjectiveStatBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function build(string $range = 'month'): Collection
    {
        $objectives = Objective::with('keyResults')->orderBy('position')->get();

        return app(ObjectiveStatBuilder::class)->build($objectives, $range);
    }

    public function test_builds_one_stat_per_seeded_objective_in_funnel_order(): void
    {
        $this->seed(OkrSeeder::class);

        $stats = $this->build();

        $this->assertSame(
            ['presentatiekwaliteit', 'onboarding', 'bedankjes', 'nieuwsbrief'],
            $stats->pluck('slug')->all(),
        );
        $this->assertSame(
            ['Fichekwaliteit', 'Activatie', 'Interactie', 'Retentie'],
            $stats->pluck('title')->all(),
        );
    }

    public function test_objective_without_a_metric_keyed_kr_is_skipped(): void
    {
        $obj = Objective::factory()->create(['slug' => 'leeg', 'title' => 'Leeg', 'position' => 1]);
        KeyResult::factory()->create(['objective_id' => $obj->id, 'metric_key' => null]);

        $stats = $this->build();

        $this->assertCount(0, $stats);
    }

    public function test_activatie_headline_uses_onboarding_signup_count(): void
    {
        $this->seed(OkrSeeder::class);
        User::factory()->count(3)->create(['role' => 'contributor']);

        $stats = $this->build();
        $activatie = $stats->firstWhere('slug', 'onboarding');

        $expected = app(MetricRegistry::class)->compute('onboarding_signup_count', 'month');
        $this->assertSame($expected->current, $activatie->value->current);
    }

    public function test_objective_with_no_key_results_is_skipped(): void
    {
        Objective::factory()->create(['slug' => 'kr-loos', 'title' => 'KR-loos', 'position' => 1]);

        $stats = $this->build();

        $this->assertCount(0, $stats);
    }

    public function test_target_and_metric_key_propagate_from_primary_kr(): void
    {
        $obj = Objective::factory()->create(['slug' => 'bedankjes', 'title' => 'Interactie', 'position' => 1]);
        KeyResult::factory()->create([
            'objective_id' => $obj->id,
            'metric_key' => 'thank_rate',
            'target_value' => 50,
            'target_unit' => '%',
            'position' => 0,
        ]);

        $stat = $this->build()->firstWhere('slug', 'bedankjes');

        $this->assertSame(50, $stat->target);
        $this->assertSame('thank_rate', $stat->metricKey);
    }

    public function test_target_is_null_when_primary_kr_has_no_target(): void
    {
        $obj = Objective::factory()->create(['slug' => 'onboarding', 'title' => 'Activatie', 'position' => 1]);
        KeyResult::factory()->create([
            'objective_id' => $obj->id,
            'metric_key' => 'onboarding_signup_count',
            'target_value' => null,
            'position' => 0,
        ]);

        $stat = $this->build()->firstWhere('slug', 'onboarding');

        $this->assertNull($stat->target);
        $this->assertSame('onboarding_signup_count', $stat->metricKey);
    }
}
