<?php

namespace Tests\Feature\Okr;

use App\Models\Okr\Initiative;
use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use App\Models\OnboardingEmailLog;
use App\Models\User;
use App\Services\Okr\InitiativeImpact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitiativeImpactTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_includes_one_kr_impact_per_objective_kr(): void
    {
        $objective = Objective::factory()->create();
        $kr1 = KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'onboarding_signup_count',
            'label' => 'Aanmeldingen',
        ]);
        $kr2 = KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'onboarding_verification_rate',
            'label' => 'E-mailverificatie',
        ]);

        // 1 signup before initiative starts
        User::factory()->create(['created_at' => '2026-04-01', 'role' => 'contributor']);

        $initiative = Initiative::create([
            'objective_id' => $objective->id,
            'slug' => 'test',
            'label' => 'Test',
            'status' => 'in_progress',
            'started_at' => '2026-04-15',
            'position' => 1,
        ]);

        // 2 more signups after initiative starts
        User::factory()->count(2)->create(['created_at' => now(), 'role' => 'contributor']);

        $summary = app(InitiativeImpact::class)->forInitiative($initiative->fresh());

        $this->assertCount(2, $summary->krImpacts);

        $signupImpact = $summary->krImpactFor($kr1->id);
        $this->assertSame('Aanmeldingen', $signupImpact->krLabel);
        $this->assertSame(1.0, (float) $signupImpact->baselineValue);
        $this->assertSame(3, $signupImpact->currentValue);
        $this->assertSame(2, $signupImpact->delta);
    }

    public function test_reactivation_initiative_exposes_daily_effort_result_breakdown(): void
    {
        $objective = Objective::factory()->create(['slug' => 'reactivatie', 'title' => 'Reactivatie']);
        KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'reactivation_rate',
            'label' => 'Slapers terug actief',
        ]);

        $initiative = Initiative::create([
            'objective_id' => $objective->id,
            'slug' => 'reactivatie-campagne',
            'label' => 'Reactivatie-campagne',
            'status' => 'in_progress',
            'started_at' => now()->subDays(2)->toDateString(),
            'position' => 1,
        ]);

        $back = User::factory()->create(['last_visited_at' => now()]);
        $never = User::factory()->create(['last_visited_at' => null]);
        foreach ([$back, $never] as $user) {
            OnboardingEmailLog::create([
                'user_id' => $user->id,
                'mail_key' => config('newsletter.reactivation_mail_key'),
                'sent_at' => now()->subDay(),
            ]);
        }

        $impact = app(InitiativeImpact::class)->forInitiative($initiative->fresh())->krImpacts->first();

        // Concrete breakdown is present; the abstract rate trajectory is not.
        $this->assertSame('verstuurde mails', $impact->effortLabel);
        $this->assertSame('opnieuw actief', $impact->resultLabel);
        $this->assertSame([], $impact->sparkline);
        $this->assertNotEmpty($impact->breakdown);
        $this->assertSame(2, array_sum(array_column($impact->breakdown, 'effort')));
        $this->assertSame(1, array_sum(array_column($impact->breakdown, 'result')));
    }

    public function test_metric_without_breakdown_uses_rate_trajectory(): void
    {
        $objective = Objective::factory()->create();
        KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'onboarding_signup_count',
        ]);
        $initiative = Initiative::create([
            'objective_id' => $objective->id,
            'slug' => 'geen-breakdown',
            'label' => 'Geen breakdown',
            'status' => 'in_progress',
            'started_at' => now()->subDays(3)->toDateString(),
            'position' => 1,
        ]);

        $impact = app(InitiativeImpact::class)->forInitiative($initiative->fresh())->krImpacts->first();

        $this->assertNull($impact->effortLabel);
        $this->assertSame([], $impact->breakdown);
        $this->assertNotEmpty($impact->sparkline);
    }

    public function test_recent_initiative_uses_a_daily_trajectory(): void
    {
        $objective = Objective::factory()->create();
        KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'onboarding_signup_count',
        ]);

        $initiative = Initiative::create([
            'objective_id' => $objective->id,
            'slug' => 'recent',
            'label' => 'Recent',
            'status' => 'in_progress',
            'started_at' => now()->subDays(4)->toDateString(),
            'position' => 1,
        ]);

        $impact = app(InitiativeImpact::class)->forInitiative($initiative->fresh())->krImpacts->first();

        $this->assertSame('dag', $impact->periodWord);
        // 7 lead days + 4 since launch + today ≈ 12 daily buckets, well above
        // the ~5 a weekly trajectory of the same span would produce.
        $this->assertGreaterThan(8, count($impact->sparkline));
    }

    public function test_long_running_initiative_falls_back_to_weekly_trajectory(): void
    {
        $objective = Objective::factory()->create();
        KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'onboarding_signup_count',
        ]);

        $initiative = Initiative::create([
            'objective_id' => $objective->id,
            'slug' => 'oud',
            'label' => 'Oud',
            'status' => 'in_progress',
            'started_at' => now()->subDays(60)->toDateString(),
            'position' => 1,
        ]);

        $impact = app(InitiativeImpact::class)->forInitiative($initiative->fresh())->krImpacts->first();

        $this->assertSame('week', $impact->periodWord);
        // 60 days as weekly buckets is well under what a daily trajectory
        // (~67 buckets) would produce.
        $this->assertLessThan(20, count($impact->sparkline));
    }

    public function test_summary_marker_index_falls_within_sparkline(): void
    {
        $objective = Objective::factory()->create();
        KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'onboarding_signup_count',
        ]);

        $initiative = Initiative::create([
            'objective_id' => $objective->id,
            'slug' => 't',
            'label' => 't',
            'status' => 'in_progress',
            'started_at' => now()->subWeeks(2)->toDateString(),
            'position' => 1,
        ]);

        $summary = app(InitiativeImpact::class)->forInitiative($initiative->fresh());
        $impact = $summary->krImpacts->first();

        $this->assertGreaterThanOrEqual(0, $impact->markerIndex);
        $this->assertLessThan(count($impact->sparkline), $impact->markerIndex);
    }

    private function makeInitiative(): Initiative
    {
        $objective = Objective::factory()->create(['slug' => 'onboarding', 'title' => 'Activatie']);
        KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'onboarding_signup_count',
            'label' => 'Aanmeldingen',
            'position' => 1,
        ]);
        KeyResult::factory()->create([
            'objective_id' => $objective->id,
            'metric_key' => 'onboarding_verification_rate',
            'label' => 'E-mailverificatie',
            'position' => 2,
        ]);

        return Initiative::create([
            'objective_id' => $objective->id,
            'slug' => 'onboarding-emails',
            'label' => 'Onboarding-e-mails',
            'status' => 'in_progress',
            'started_at' => '2026-04-02',
            'position' => 1,
        ])->fresh();
    }

    public function test_headline_impact_returns_first_kr_with_no_sparkline(): void
    {
        $initiative = $this->makeInitiative();

        $headline = app(InitiativeImpact::class)->headlineImpact($initiative);

        $this->assertNotNull($headline);
        $this->assertSame('Aanmeldingen', $headline->krLabel);
        $this->assertSame([], $headline->sparkline);
        $this->assertSame(0, $headline->markerIndex);
    }

    public function test_headline_impact_matches_first_kr_of_full_summary(): void
    {
        $initiative = $this->makeInitiative();
        $service = app(InitiativeImpact::class);

        $headline = $service->headlineImpact($initiative);
        $full = $service->forInitiative($initiative)->krImpacts->first();

        $this->assertSame($full->krId, $headline->krId);
        $this->assertSame($full->delta, $headline->delta);
        $this->assertSame($full->currentValue, $headline->currentValue);
        $this->assertSame($full->baselineValue, $headline->baselineValue);
    }

    public function test_headline_impact_is_null_when_objective_has_no_key_results(): void
    {
        $objective = Objective::factory()->create(['slug' => 'leeg', 'title' => 'Leeg']);
        $initiative = Initiative::create([
            'objective_id' => $objective->id,
            'slug' => 'iets',
            'label' => 'Iets',
            'status' => 'in_progress',
            'started_at' => '2026-04-02',
            'position' => 1,
        ])->fresh();

        $this->assertNull(app(InitiativeImpact::class)->headlineImpact($initiative));
    }
}
