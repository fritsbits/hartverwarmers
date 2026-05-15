<?php

namespace Tests\Feature\Okr;

use App\Models\Okr\Initiative;
use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use App\Services\Okr\InitiativeImpact;
use App\Services\Okr\InitiativeKrImpact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitiativeRowPartialTest extends TestCase
{
    use RefreshDatabase;

    private function render(Initiative $initiative, ?InitiativeKrImpact $headline): string
    {
        return view('admin.partials.initiative-row', [
            'initiative' => $initiative,
            'headline' => $headline,
        ])->render();
    }

    public function test_renders_label_weeks_live_deeplink_and_objective(): void
    {
        $objective = Objective::factory()->create(['slug' => 'bedankjes', 'title' => 'Interactie']);
        KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'thank_rate',
            'label' => 'Bedankratio',
            'position' => 1,
        ]);
        $initiative = Initiative::create([
            'objective_id' => $objective->id,
            'slug' => 'bedankflow',
            'label' => 'Bedankflow na download',
            'status' => 'in_progress',
            'started_at' => now()->subWeeks(3)->toDateString(),
            'position' => 1,
        ])->fresh();
        $headline = app(InitiativeImpact::class)->headlineImpact($initiative);

        $html = $this->render($initiative, $headline);

        $this->assertStringContainsString('Bedankflow na download', $html);
        $this->assertStringContainsString('3 weken live', $html);
        $this->assertStringContainsString('Interactie', $html);
        $this->assertStringContainsString('?tab=bedankjes&amp;init=bedankflow', $html);
        $this->assertStringContainsString('aria-label="Bedankflow na download"', $html);
    }

    public function test_singular_week_label(): void
    {
        $objective = Objective::factory()->create(['slug' => 'onboarding', 'title' => 'Activatie']);
        KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'onboarding_signup_count',
            'label' => 'Aanmeldingen',
            'position' => 1,
        ]);
        $initiative = Initiative::create([
            'objective_id' => $objective->id,
            'slug' => 'x',
            'label' => 'X',
            'status' => 'in_progress',
            'started_at' => now()->subWeek()->toDateString(),
            'position' => 1,
        ])->fresh();

        $html = $this->render($initiative, app(InitiativeImpact::class)->headlineImpact($initiative));

        $this->assertStringContainsString('1 week live', $html);
        $this->assertStringNotContainsString('1 weken', $html);
    }

    public function test_shows_no_measurement_when_headline_null(): void
    {
        $objective = Objective::factory()->create(['slug' => 'leeg', 'title' => 'Leeg']);
        $initiative = Initiative::create([
            'objective_id' => $objective->id,
            'slug' => 'geen-kr',
            'label' => 'Geen KR',
            'status' => 'in_progress',
            'started_at' => now()->subWeeks(2)->toDateString(),
            'position' => 1,
        ])->fresh();

        $html = $this->render($initiative, null);

        $this->assertStringContainsString('Geen KR', $html);
        $this->assertStringContainsString('Nog geen meting', $html);
    }

    public function test_positive_delta_renders_green_with_sign_and_pp_unit(): void
    {
        $objective = Objective::factory()->create(['slug' => 'bedankjes', 'title' => 'Interactie']);
        $initiative = Initiative::create([
            'objective_id' => $objective->id,
            'slug' => 'bedankflow',
            'label' => 'Bedankflow na download',
            'status' => 'in_progress',
            'started_at' => now()->subWeeks(3)->toDateString(),
            'position' => 1,
        ])->fresh();

        $headline = new InitiativeKrImpact(
            krId: 1,
            krLabel: 'Bedankratio',
            baselineValue: 30,
            currentValue: 42,
            delta: 12,
            unit: '%',
            baselineLowData: false,
            currentLowData: false,
            sparkline: [],
            markerIndex: 0,
        );

        $html = $this->render($initiative, $headline);

        $this->assertStringContainsString('+12pp', $html);
        $this->assertStringContainsString('sinds start', $html);
        $this->assertStringContainsString('text-green-700', $html);
        $this->assertStringNotContainsString('Nog geen meting', $html);
    }

    public function test_negative_delta_renders_red(): void
    {
        $objective = Objective::factory()->create(['slug' => 'nieuwsbrief', 'title' => 'Retentie']);
        $initiative = Initiative::create([
            'objective_id' => $objective->id,
            'slug' => 'nb-systeem',
            'label' => 'Nieuwsbrief-systeem',
            'status' => 'in_progress',
            'started_at' => now()->subWeeks(2)->toDateString(),
            'position' => 1,
        ])->fresh();

        $headline = new InitiativeKrImpact(
            krId: 2,
            krLabel: 'Activatie na nieuwsbrief',
            baselineValue: 50,
            currentValue: 41,
            delta: -9,
            unit: '%',
            baselineLowData: false,
            currentLowData: false,
            sparkline: [],
            markerIndex: 0,
        );

        $html = $this->render($initiative, $headline);

        $this->assertStringContainsString('-9pp', $html);
        $this->assertStringContainsString('text-red-600', $html);
    }
}
