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

    public function test_series_length_matches_range_cadence_when_data_present(): void
    {
        $this->seed(OkrSeeder::class);
        $stats = $this->build('quarter');
        $activatie = $stats->firstWhere('slug', 'onboarding');

        // onboarding_signup_count.computeAsOf returns int 0 (never null) for empty windows, so every sampled point is kept → full cadence length.
        $this->assertCount(12, $activatie->series);
        $this->assertSame(['label', 'value'], array_keys($activatie->series[0]));
    }

    public function test_all_null_metric_yields_empty_series(): void
    {
        $this->seed(OkrSeeder::class);
        $stats = $this->build();
        $fiche = $stats->firstWhere('slug', 'presentatiekwaliteit');

        // No Fiches seeded → presentation_score_avg.computeAsOf returns current=null at every sample → all points skipped.
        $this->assertSame([], $fiche->series);
    }

    public function test_objective_with_no_key_results_is_skipped(): void
    {
        Objective::factory()->create(['slug' => 'kr-loos', 'title' => 'KR-loos', 'position' => 1]);

        $stats = $this->build();

        $this->assertCount(0, $stats);
    }

    public function test_series_cadence_for_week_and_alltime_ranges(): void
    {
        $this->seed(OkrSeeder::class);

        $week = app(ObjectiveStatBuilder::class)
            ->build(Objective::with('keyResults')->orderBy('position')->get(), 'week')
            ->firstWhere('slug', 'onboarding');
        $alltime = app(ObjectiveStatBuilder::class)
            ->build(Objective::with('keyResults')->orderBy('position')->get(), 'alltime')
            ->firstWhere('slug', 'onboarding');

        $this->assertCount(8, $week->series);
        $this->assertCount(12, $alltime->series);
    }
}
