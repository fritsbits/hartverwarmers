<?php

namespace Tests\Feature\Admin;

use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Like;
use App\Models\OnboardingEmailLog;
use App\Models\User;
use App\Models\UserInteraction;
use Database\Seeders\OkrSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_contributor_gets_403(): void
    {
        $user = User::factory()->create(['role' => 'contributor']);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_curator_gets_403(): void
    {
        $user = User::factory()->create(['role' => 'curator']);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_dashboard(): void
    {
        $this->seed(OkrSeeder::class);
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('admin.dashboard').'?tab=presentatiekwaliteit');

        $response->assertOk();
        $response->assertSee('Gemiddelde presentatiescore');
        $response->assertSee('AI-suggesties');
    }

    public function test_default_range_is_month(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=presentatiekwaliteit');

        $response->assertOk();
        $this->assertEquals('month', $response->viewData('range'));
        // Month range builds 4 weekly slots
        $this->assertCount(4, $response->viewData('weeklyTrend'));
    }

    public function test_week_range_builds_seven_daily_slots(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=presentatiekwaliteit&range=week');

        $response->assertOk();
        $this->assertEquals('week', $response->viewData('range'));
        $this->assertCount(7, $response->viewData('weeklyTrend'));
    }

    public function test_quarter_range_builds_thirteen_weekly_slots(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=presentatiekwaliteit&range=quarter');

        $response->assertOk();
        $this->assertEquals('quarter', $response->viewData('range'));
        $this->assertCount(13, $response->viewData('weeklyTrend'));
    }

    public function test_alltime_range_builds_monthly_slots_from_first_scored_fiche(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Fiche::factory()->published()->withPresentationScore(60)->create([
            'quality_assessed_at' => now()->subMonths(2)->startOfMonth()->addDays(5),
        ]);
        Fiche::factory()->published()->withPresentationScore(80)->create([
            'quality_assessed_at' => now()->startOfMonth()->addDays(2),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=presentatiekwaliteit&range=alltime');

        $response->assertOk();
        $this->assertEquals('alltime', $response->viewData('range'));
        $trend = $response->viewData('weeklyTrend');
        // 3 monthly buckets from subMonths(2) through current month
        $this->assertCount(3, $trend);
    }

    public function test_invalid_range_falls_back_to_month(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?range=garbage');

        $response->assertOk();
        $this->assertEquals('month', $response->viewData('range'));
    }

    public function test_month_range_builds_four_weekly_slots(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=presentatiekwaliteit&range=month');

        $response->assertOk();
        $this->assertEquals('month', $response->viewData('range'));
        $this->assertCount(4, $response->viewData('weeklyTrend'));
    }

    public function test_weekly_trend_groups_by_day_in_week_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Two fiches assessed on different days within the last 7 days
        Fiche::factory()->published()->withPresentationScore(40)->create([
            'quality_assessed_at' => now()->subDays(3),
        ]);
        Fiche::factory()->published()->withPresentationScore(80)->create([
            'quality_assessed_at' => now(),
        ]);
        // Fiche outside the 7-day window — should be excluded
        Fiche::factory()->published()->withPresentationScore(60)->create([
            'quality_assessed_at' => now()->subDays(10),
        ]);
        // Unpublished should be excluded
        Fiche::factory()->withPresentationScore(99)->create([
            'quality_assessed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=presentatiekwaliteit&range=week');

        $response->assertOk();
        $trend = $response->viewData('weeklyTrend');
        $scored = array_filter($trend, fn ($w) => $w['avg_score'] !== null);
        $this->assertCount(2, $scored);
    }

    public function test_weekly_trend_groups_by_week_in_month_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Two fiches assessed in different weeks within the last 4 weeks
        Fiche::factory()->published()->withPresentationScore(40)->create([
            'quality_assessed_at' => now()->subWeeks(2)->startOfWeek(),
        ]);
        Fiche::factory()->published()->withPresentationScore(80)->create([
            'quality_assessed_at' => now()->startOfWeek(),
        ]);
        // Fiche outside 4-week window — should be excluded
        Fiche::factory()->published()->withPresentationScore(60)->create([
            'quality_assessed_at' => now()->subWeeks(5),
        ]);
        // Unpublished should be excluded
        Fiche::factory()->withPresentationScore(99)->create([
            'quality_assessed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=presentatiekwaliteit&range=month');

        $response->assertOk();
        $trend = $response->viewData('weeklyTrend');
        $scored = array_filter($trend, fn ($w) => $w['avg_score'] !== null);
        $this->assertCount(2, $scored);
    }

    public function test_trend_delta_hidden_when_fewer_than_two_slots(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Fiche::factory()->published()->withPresentationScore(60)->create([
            'quality_assessed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertNull($response->viewData('trendDelta'));
    }

    public function test_trend_delta_is_last_minus_first_slot(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Use month range so two different weeks are within window
        Fiche::factory()->published()->withPresentationScore(30)->create([
            'quality_assessed_at' => now()->subWeeks(3)->startOfWeek(),
        ]);
        Fiche::factory()->published()->withPresentationScore(70)->create([
            'quality_assessed_at' => now()->startOfWeek(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=presentatiekwaliteit&range=month');

        $response->assertOk();
        $this->assertEquals(40, $response->viewData('trendDelta'));
    }

    public function test_empty_trend_when_no_scored_fiches(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $trend = $response->viewData('weeklyTrend');
        $this->assertEmpty(array_filter($trend, fn ($w) => $w['avg_score'] !== null));
        $this->assertNull($response->viewData('trendDelta'));
    }

    public function test_last_five_fiches_ordered_by_created_at(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create 6 fiches with explicit created_at so ordering is deterministic
        for ($i = 1; $i <= 6; $i++) {
            Fiche::factory()->published()->withPresentationScore($i * 10)->create([
                'created_at' => now()->subDays(7 - $i),
            ]);
        }
        // Unpublished — excluded
        Fiche::factory()->withPresentationScore(99)->create(['created_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=presentatiekwaliteit');

        $response->assertOk();
        $fiches = $response->viewData('lastFiches');
        $this->assertCount(5, $fiches);
        // Most recent first: score 60 (day 1 ago), then 50, 40, 30, 20
        $this->assertEquals(60, $fiches->first()->presentation_score);
    }

    public function test_last_five_fiches_includes_fiches_without_score(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // 3 recent fiches without a score, 3 older with a score
        for ($i = 1; $i <= 3; $i++) {
            Fiche::factory()->published()->create([
                'presentation_score' => null,
                'created_at' => now()->subDays($i),
            ]);
        }
        for ($i = 4; $i <= 6; $i++) {
            Fiche::factory()->published()->withPresentationScore(80)->create([
                'created_at' => now()->subDays($i),
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=presentatiekwaliteit');

        $response->assertOk();
        $fiches = $response->viewData('lastFiches');
        $this->assertCount(5, $fiches);
        // The 3 most recent (no score) come first
        $this->assertNull($fiches->first()->presentation_score);
    }

    public function test_last_five_average_vs_global_average(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // 3 older fiches score 20 each
        for ($i = 0; $i < 3; $i++) {
            Fiche::factory()->published()->withPresentationScore(20)->create([
                'created_at' => now()->subDays(10 + $i),
            ]);
        }
        // 5 recent fiches score 80 each
        for ($i = 0; $i < 5; $i++) {
            Fiche::factory()->published()->withPresentationScore(80)->create([
                'created_at' => now()->subDays($i),
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=presentatiekwaliteit');

        $response->assertOk();
        $this->assertEquals(80, $response->viewData('lastFiveAvg'));
        // Global: (3*20 + 5*80) / 8 = 460/8 = 57.5 → rounds to 58
        $this->assertEquals(58, $response->viewData('globalAvg'));
    }

    public function test_adoption_headline_excludes_fiches_with_only_empty_suggestions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Has suggestions but all fields empty — should NOT count in denominator
        Fiche::factory()->published()->create([
            'ai_suggestions' => ['title' => '', 'description' => '', 'preparation' => '', 'inventory' => '', 'process' => '', 'applied' => []],
        ]);
        // Has real suggestions, none applied
        Fiche::factory()->published()->withSuggestions()->create();
        // Has real suggestions, one applied
        Fiche::factory()->published()->withSuggestions(['applied' => ['title']])->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=presentatiekwaliteit');

        $response->assertOk();
        // Denominator = 2 (fiches with non-empty suggestions), numerator = 1
        $this->assertEquals(2, $response->viewData('withSuggestions'));
        $this->assertEquals(1, $response->viewData('withAnyApplied'));
        $this->assertEquals(50, $response->viewData('adoptionRate'));
    }

    public function test_adoption_rate_is_zero_when_no_suggestions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=presentatiekwaliteit');

        $response->assertOk();
        $this->assertEquals(0, $response->viewData('adoptionRate'));
    }

    public function test_adoption_excludes_fiches_outside_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Fiche created 2 months ago — outside the default week range
        Fiche::factory()->published()->withSuggestions(['applied' => ['title']])->create([
            'created_at' => now()->subMonths(2),
        ]);
        // Fiche created today — inside the week range
        Fiche::factory()->published()->withSuggestions()->create([
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=presentatiekwaliteit');

        $response->assertOk();
        // Only the recent fiche is counted
        $this->assertEquals(1, $response->viewData('withSuggestions'));
        $this->assertEquals(0, $response->viewData('withAnyApplied'));
    }

    public function test_per_field_adoption_rates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Fiche with title suggestion, title applied
        Fiche::factory()->published()->withSuggestions(['applied' => ['title']])->create();
        // Fiche with title suggestion, not applied
        Fiche::factory()->published()->withSuggestions()->create();
        // Fiche with title suggestion only (no description), not applied
        Fiche::factory()->published()->create([
            'ai_suggestions' => ['title' => 'Suggested title', 'description' => '', 'preparation' => '', 'inventory' => '', 'process' => '', 'applied' => []],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=presentatiekwaliteit');

        $response->assertOk();
        $fieldAdoption = $response->viewData('fieldAdoption');
        // title: 3 had suggestion, 1 applied → 33%
        $this->assertEquals(3, $fieldAdoption['title']['suggested']);
        $this->assertEquals(1, $fieldAdoption['title']['applied']);
        $this->assertEquals(33, $fieldAdoption['title']['rate']);
        // description: 2 had non-empty suggestion, 0 applied
        $this->assertEquals(2, $fieldAdoption['description']['suggested']);
        $this->assertEquals(0, $fieldAdoption['description']['applied']);
        $this->assertEquals(0, $fieldAdoption['description']['rate']);
    }

    public function test_fiche_adoption_details_contains_per_fiche_breakdown(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Fiche with title suggestion NOT applied, description suggestion applied
        Fiche::factory()->published()->withSuggestions(['applied' => ['description']])->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=presentatiekwaliteit');

        $response->assertOk();
        $details = $response->viewData('ficheAdoptionDetails');
        $this->assertCount(1, $details);

        $detail = $details[0];
        $this->assertNotEmpty($detail['title']);
        $this->assertNotEmpty($detail['url']);
        // title is suggested but not applied
        $this->assertTrue($detail['fields']['title']['suggested']);
        $this->assertFalse($detail['fields']['title']['applied']);
        // description is suggested and applied
        $this->assertTrue($detail['fields']['description']['suggested']);
        $this->assertTrue($detail['fields']['description']['applied']);
        // adoptedCount = 1 (description), suggestedCount >= 1
        $this->assertEquals(1, $detail['adoptedCount']);
        $this->assertGreaterThanOrEqual(1, $detail['suggestedCount']);
    }

    public function test_onboarding_tab_is_accessible(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=onboarding');

        $response->assertOk();
        $response->assertViewHas('onboardingStats');
        $response->assertViewHas('onboardingEmailCounts');
    }

    public function test_kr1_counts_users_with_first_return_at_set(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // New user who returned
        User::factory()->create([
            'email_verified_at' => now()->subDays(5),
            'first_return_at' => now()->subDays(4),
        ]);
        // New user who did not return
        User::factory()->create([
            'email_verified_at' => now()->subDays(5),
            'first_return_at' => null,
        ]);
        // Old user — outside 30-day cohort (created_at defines the cohort window)
        User::factory()->create([
            'created_at' => now()->subDays(35),
            'email_verified_at' => now()->subDays(35),
            'first_return_at' => now()->subDays(34),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=onboarding');

        $stats = $response->viewData('onboardingStats');
        $this->assertEquals(2, $stats['newUsersCount']);
        $this->assertEquals(1, $stats['kr1Count']);
        $this->assertEquals(50, $stats['kr1Percentage']);
    }

    public function test_kr2_counts_users_who_gave_kudos_or_comment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $userWithKudos = User::factory()->create(['email_verified_at' => now()->subDays(10)]);
        $userWithComment = User::factory()->create(['email_verified_at' => now()->subDays(10)]);
        $userWithNothing = User::factory()->create(['email_verified_at' => now()->subDays(10)]);
        $fiche = Fiche::factory()->published()->create();

        // kudos
        Like::create([
            'user_id' => $userWithKudos->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 1,
            'created_at' => now()->subDays(8),
        ]);
        // comment
        Comment::create([
            'user_id' => $userWithComment->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'body' => 'Goed gedaan!',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=onboarding');

        $stats = $response->viewData('onboardingStats');
        $this->assertEquals(3, $stats['newUsersCount']);
        $this->assertEquals(2, $stats['kr2Count']);
        $this->assertEquals(67, $stats['kr2Percentage']);
    }

    public function test_kr3_returns_null_when_no_follow_up_emails_sent(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=onboarding');

        $stats = $response->viewData('onboardingStats');
        $this->assertNull($stats['kr3Percentage']);
        $this->assertEquals(0, $stats['kr3SentCount']);
    }

    public function test_email_counts_include_all_mail_keys(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        OnboardingEmailLog::create(['user_id' => $user->id, 'mail_key' => 'mail_1', 'sent_at' => now()->subDays(5)]);
        OnboardingEmailLog::create(['user_id' => $user->id, 'mail_key' => 'mail_4', 'sent_at' => now()->subDays(3)]); // first bookmark (from LikeObserver)
        OnboardingEmailLog::create(['user_id' => $user->id, 'mail_key' => "download_followup_{$fiche->id}", 'sent_at' => now()->subDay()]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=onboarding');

        $counts = $response->viewData('onboardingEmailCounts');
        $this->assertEquals(1, $counts['mail_1']);
        $this->assertEquals(1, $counts['mail_4']);
        $this->assertEquals(1, $counts['download_followup']);
    }

    public function test_aanmeldingen_tab_falls_back_to_overzicht(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen');

        $response->assertOk();
        $this->assertEquals('overzicht', $response->viewData('tab'));
    }

    public function test_aanmeldingen_slug_falls_back_to_overzicht(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['tab' => 'aanmeldingen']));

        $response->assertOk();
        // 'aanmeldingen' is no longer in the valid tab list → falls back to overzicht.
        // The overview shows all 4 objectives as cards; 'Interactie' is unique to that view
        // and would NOT appear if we somehow rendered an old aanmeldingen tab content.
        $response->assertSee('Interactie');
        $response->assertSee('Retentie');
    }

    public function test_default_tab_defaults_to_month_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertEquals('month', $response->viewData('range'));
    }

    public function test_valid_tabs_accept_quarter_and_alltime_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $quarter = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=overzicht&range=quarter');
        $this->assertEquals('quarter', $quarter->viewData('range'));

        $alltime = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=overzicht&range=alltime');
        $this->assertEquals('alltime', $alltime->viewData('range'));
    }

    public function test_all_tabs_share_unified_default_range_of_month(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['overzicht', 'presentatiekwaliteit', 'onboarding', 'bedankjes', 'nieuwsbrief'] as $tab) {
            $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab='.$tab);
            $response->assertOk();
            $this->assertEquals('month', $response->viewData('range'), "tab={$tab} should default to month");
        }
    }

    public function test_invalid_tab_falls_back_to_overzicht(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=garbage');

        $response->assertOk();
        $this->assertEquals('overzicht', $response->viewData('tab'));
    }

    public function test_signup_trend_is_empty_since_aanmeldingen_tab_removed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $this->assertEmpty($response->viewData('signupTrend'));
        $this->assertEmpty($response->viewData('signupStats'));
    }

    public function test_signup_trend_is_only_populated_for_onboarding_tab(): void
    {
        // signupTrend is populated only for the onboarding tab (where it powers the KR1 sparkline).
        // All other tabs receive empty arrays to avoid unnecessary queries.
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['overzicht', 'presentatiekwaliteit', 'bedankjes', 'nieuwsbrief'] as $tab) {
            $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab='.$tab);
            $this->assertEmpty($response->viewData('signupTrend'), "signupTrend should be empty for tab={$tab}");
            $this->assertEmpty($response->viewData('signupStats'), "signupStats should be empty for tab={$tab}");
        }

        $onboardingResponse = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=onboarding');
        $this->assertNotEmpty($onboardingResponse->viewData('signupTrend'), 'signupTrend should be populated for tab=onboarding');
        $this->assertNotEmpty($onboardingResponse->viewData('signupStats'), 'signupStats should be populated for tab=onboarding');
    }

    public function test_signup_stats_and_trend_are_empty_after_aanmeldingen_removal(): void
    {
        // The aanmeldingen tab was removed in favour of the OKR-based Onboarding tab.
        // signupStats and signupTrend are no longer populated by the controller.
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=overzicht');

        $response->assertOk();
        $this->assertEmpty($response->viewData('signupStats'));
        $this->assertEmpty($response->viewData('signupTrend'));
    }

    public function test_onboarding_cohort_respects_week_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Within last 7 days — counts
        User::factory()->create(['created_at' => now()->subDays(3)]);
        // Outside 7-day window but within 30 — does NOT count for week range
        User::factory()->create(['created_at' => now()->subDays(15)]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=onboarding&range=week');

        $stats = $response->viewData('onboardingStats');
        $this->assertEquals(1, $stats['newUsersCount']);
        $this->assertEquals('laatste week', $stats['rangeLabel']);
    }

    public function test_onboarding_cohort_respects_quarter_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->create(['created_at' => now()->subDays(5)]);
        User::factory()->create(['created_at' => now()->subDays(60)]);
        // Outside 90-day window — excluded
        User::factory()->create(['created_at' => now()->subDays(120)]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=onboarding&range=quarter');

        $stats = $response->viewData('onboardingStats');
        $this->assertEquals(2, $stats['newUsersCount']);
        $this->assertEquals('laatste 3 maanden', $stats['rangeLabel']);
    }

    public function test_onboarding_cohort_alltime_includes_all_non_admin_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->create(['created_at' => now()->subYear()]);
        User::factory()->create(['created_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=onboarding&range=alltime');

        $stats = $response->viewData('onboardingStats');
        $this->assertEquals(2, $stats['newUsersCount']);
        $this->assertEquals('sinds start', $stats['rangeLabel']);
    }

    public function test_bedankjes_tab_is_accessible(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes');

        $response->assertOk();
        $response->assertViewHas('thankTrend');
        $response->assertViewHas('thankStats');
        $this->assertEquals('bedankjes', $response->viewData('tab'));
    }

    public function test_thank_rate_counts_post_download_kudos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
            'created_at' => now()->subDays(5),
        ]);
        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 3,
            'created_at' => now()->subDays(4),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes&range=month');

        $stats = $response->viewData('thankStats');
        $this->assertEquals(1, $stats['currentDownloads']);
        $this->assertEquals(1, $stats['currentThanked']);
        $this->assertEquals(100, $stats['currentRate']);
    }

    public function test_thank_rate_ignores_pre_download_kudos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 1,
            'created_at' => now()->subDays(10),
        ]);
        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
            'created_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes&range=month');

        $stats = $response->viewData('thankStats');
        $this->assertEquals(1, $stats['currentDownloads']);
        $this->assertEquals(0, $stats['currentThanked']);
        $this->assertEquals(0, $stats['currentRate']);
    }

    public function test_thank_rate_ignores_anonymous_kudos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
            'created_at' => now()->subDays(5),
        ]);
        Like::create([
            'user_id' => null,
            'session_id' => 'someanonsession',
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 2,
            'created_at' => now()->subDays(3),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes&range=month');

        $stats = $response->viewData('thankStats');
        $this->assertEquals(1, $stats['currentDownloads']);
        $this->assertEquals(0, $stats['currentThanked']);
    }

    public function test_thank_rate_counts_post_download_comments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
            'created_at' => now()->subDays(5),
        ]);
        Comment::create([
            'user_id' => $user->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'body' => 'Heel goed idee!',
            'created_at' => now()->subDays(4),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes&range=month');

        $stats = $response->viewData('thankStats');
        $this->assertEquals(1, $stats['currentThanked']);
        $this->assertEquals(1, $stats['commentThankCount']);
        $this->assertEquals(0, $stats['kudosThankCount']);
    }

    public function test_thank_rate_ignores_soft_deleted_comments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
            'created_at' => now()->subDays(5),
        ]);
        $comment = Comment::create([
            'user_id' => $user->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'body' => 'Heel goed idee!',
            'created_at' => now()->subDays(4),
        ]);
        $comment->delete();

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes&range=month');

        $stats = $response->viewData('thankStats');
        $this->assertEquals(0, $stats['currentThanked']);
    }

    public function test_kudos_and_comment_overlap_counted_in_both_split_buckets(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
            'created_at' => now()->subDays(5),
        ]);
        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 1,
            'created_at' => now()->subDays(4),
        ]);
        Comment::create([
            'user_id' => $user->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'body' => 'Top!',
            'created_at' => now()->subDays(3),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes&range=month');

        $stats = $response->viewData('thankStats');
        $this->assertEquals(1, $stats['currentThanked']);          // de-duped: one thanked download
        $this->assertEquals(1, $stats['kudosThankCount']);         // counts kudos
        $this->assertEquals(1, $stats['commentThankCount']);       // also counts comment
    }

    public function test_thank_rate_ignores_zero_count_kudos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
            'created_at' => now()->subDays(5),
        ]);
        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 0,
            'created_at' => now()->subDays(4),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes&range=month');

        $stats = $response->viewData('thankStats');
        $this->assertEquals(0, $stats['currentThanked']);
    }

    public function test_thank_stats_month_delta_compares_current_vs_previous(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $now = now();

        // Previous 30-day window: 4 downloads, 1 thanked → 25%
        for ($i = 0; $i < 4; $i++) {
            $u = User::factory()->create();
            $f = Fiche::factory()->published()->create();
            UserInteraction::create([
                'user_id' => $u->id, 'interactable_type' => Fiche::class, 'interactable_id' => $f->id,
                'type' => 'download', 'created_at' => $now->copy()->subDays(45 + $i),
            ]);
            if ($i === 0) {
                Like::create([
                    'user_id' => $u->id, 'likeable_type' => Fiche::class, 'likeable_id' => $f->id,
                    'type' => 'kudos', 'count' => 1, 'created_at' => $now->copy()->subDays(44 + $i),
                ]);
            }
        }

        // Current 30-day window: 2 downloads, 1 thanked → 50%
        for ($i = 0; $i < 2; $i++) {
            $u = User::factory()->create();
            $f = Fiche::factory()->published()->create();
            UserInteraction::create([
                'user_id' => $u->id, 'interactable_type' => Fiche::class, 'interactable_id' => $f->id,
                'type' => 'download', 'created_at' => $now->copy()->subDays(5 + $i),
            ]);
            if ($i === 0) {
                Like::create([
                    'user_id' => $u->id, 'likeable_type' => Fiche::class, 'likeable_id' => $f->id,
                    'type' => 'kudos', 'count' => 1, 'created_at' => $now->copy()->subDays(4 + $i),
                ]);
            }
        }

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes&range=month');

        $stats = $response->viewData('thankStats');
        $this->assertEquals(50, $stats['currentRate']);
        $this->assertEquals(25, $stats['previousRate']);
        $this->assertEquals(25, $stats['delta']);
    }

    public function test_thank_stats_alltime_has_no_delta(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        UserInteraction::create([
            'user_id' => $user->id, 'interactable_type' => Fiche::class, 'interactable_id' => $fiche->id,
            'type' => 'download', 'created_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes&range=alltime');

        $stats = $response->viewData('thankStats');
        $this->assertNull($stats['previousRate']);
        $this->assertNull($stats['delta']);
        $this->assertEquals('sinds start', $stats['rangeLabel']);
    }

    public function test_thank_stats_low_data_flag_under_five_downloads(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        for ($i = 0; $i < 3; $i++) {
            $u = User::factory()->create();
            $f = Fiche::factory()->published()->create();
            UserInteraction::create([
                'user_id' => $u->id, 'interactable_type' => Fiche::class, 'interactable_id' => $f->id,
                'type' => 'download', 'created_at' => now()->subDays(2),
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes&range=month');

        $stats = $response->viewData('thankStats');
        $this->assertTrue($stats['lowData']);
        $this->assertEquals(3, $stats['currentDownloads']);
    }

    public function test_thank_stats_total_thanked_alltime_is_independent_of_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Old thanked download — outside any current window
        $u = User::factory()->create();
        $f = Fiche::factory()->published()->create();
        UserInteraction::create([
            'user_id' => $u->id, 'interactable_type' => Fiche::class, 'interactable_id' => $f->id,
            'type' => 'download', 'created_at' => now()->subYear(),
        ]);
        Like::create([
            'user_id' => $u->id, 'likeable_type' => Fiche::class, 'likeable_id' => $f->id,
            'type' => 'kudos', 'count' => 1, 'created_at' => now()->subYear()->addDay(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes&range=week');

        $stats = $response->viewData('thankStats');
        $this->assertEquals(0, $stats['currentThanked']);          // week window: nothing
        $this->assertEquals(1, $stats['totalThankedAllTime']);     // lifetime: 1
    }

    public function test_thank_stats_empty_state_when_no_downloads(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes&range=month');

        $stats = $response->viewData('thankStats');
        $this->assertEquals(0, $stats['currentDownloads']);
        $this->assertEquals(0, $stats['currentThanked']);
        $this->assertEquals(0, $stats['currentRate']);
        $this->assertFalse($stats['lowData']);
        $this->assertEquals(0, $stats['totalThankedAllTime']);
    }

    public function test_thank_trend_month_produces_30_daily_buckets(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Download 2 days ago — thanked
        $u1 = User::factory()->create();
        $f1 = Fiche::factory()->published()->create();
        UserInteraction::create([
            'user_id' => $u1->id, 'interactable_type' => Fiche::class, 'interactable_id' => $f1->id,
            'type' => 'download', 'created_at' => now()->subDays(2),
        ]);
        Like::create([
            'user_id' => $u1->id, 'likeable_type' => Fiche::class, 'likeable_id' => $f1->id,
            'type' => 'kudos', 'count' => 1, 'created_at' => now()->subDays(1),
        ]);
        // Download 2 days ago — not thanked
        $u2 = User::factory()->create();
        $f2 = Fiche::factory()->published()->create();
        UserInteraction::create([
            'user_id' => $u2->id, 'interactable_type' => Fiche::class, 'interactable_id' => $f2->id,
            'type' => 'download', 'created_at' => now()->subDays(2),
        ]);
        // Older download outside window
        $u3 = User::factory()->create();
        $f3 = Fiche::factory()->published()->create();
        UserInteraction::create([
            'user_id' => $u3->id, 'interactable_type' => Fiche::class, 'interactable_id' => $f3->id,
            'type' => 'download', 'created_at' => now()->subDays(40),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes&range=month');

        $trend = $response->viewData('thankTrend');
        $this->assertCount(30, $trend);
        $bucket = collect($trend)->firstWhere('key', now()->subDays(2)->format('Y-m-d'));
        $this->assertEquals(2, $bucket['downloads']);
        $this->assertEquals(1, $bucket['thanked']);
        $this->assertEquals(50, $bucket['rate']);
    }

    public function test_thank_trend_week_produces_seven_daily_buckets(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes&range=week');

        $trend = $response->viewData('thankTrend');
        $this->assertCount(7, $trend);
    }

    public function test_thank_trend_quarter_produces_thirteen_weekly_buckets(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes&range=quarter');

        $trend = $response->viewData('thankTrend');
        $this->assertCount(13, $trend);
    }

    public function test_thank_trend_alltime_starts_at_earliest_download_month(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $u1 = User::factory()->create();
        $f1 = Fiche::factory()->published()->create();
        UserInteraction::create([
            'user_id' => $u1->id, 'interactable_type' => Fiche::class, 'interactable_id' => $f1->id,
            'type' => 'download', 'created_at' => now()->subMonths(2)->startOfMonth()->addDays(5),
        ]);
        $u2 = User::factory()->create();
        $f2 = Fiche::factory()->published()->create();
        UserInteraction::create([
            'user_id' => $u2->id, 'interactable_type' => Fiche::class, 'interactable_id' => $f2->id,
            'type' => 'download', 'created_at' => now()->startOfMonth()->addDays(2),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes&range=alltime');

        $trend = $response->viewData('thankTrend');
        $this->assertCount(3, $trend);  // subMonths(2), subMonth(1), current
        $this->assertEquals(1, $trend[0]['downloads']);
        $this->assertEquals(0, $trend[1]['downloads']);
        $this->assertEquals(1, $trend[2]['downloads']);
    }

    public function test_thank_trend_alltime_returns_empty_when_no_downloads(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes&range=alltime');

        $trend = $response->viewData('thankTrend');
        $this->assertEmpty($trend);
    }

    public function test_bedankjes_tab_renders_expected_copy(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes');

        $response->assertOk();
        $response->assertSee('Bedankratio');
        $response->assertSee('Hoe bedanken mensen');
        $response->assertSee('Aandeel downloads door leden dat bedankt werd', false);
    }

    public function test_nieuwsbrief_tab_is_accessible(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=nieuwsbrief');

        $response->assertOk();
        $response->assertViewHas('newsletterTrend');
        $response->assertViewHas('newsletterStats');
        $response->assertViewHas('unsubscribeByCycle');
        $response->assertViewHas('activationStats');
        $response->assertViewHas('upcomingNewsletterSends');
        $this->assertEquals('nieuwsbrief', $response->viewData('tab'));
    }

    public function test_nieuwsbrief_tab_renders_expected_copy(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=nieuwsbrief');

        $response->assertOk();
        $response->assertSee('Verstuurd');
        $response->assertSee('Uitschrijfratio per cyclus');
        $response->assertSee('Activatie na nieuwsbrief');
        $response->assertSee('Aankomende sends');
    }

    public function test_newsletter_trend_month_produces_30_daily_buckets(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $u1 = User::factory()->create(['role' => 'contributor']);
        $u2 = User::factory()->create(['role' => 'contributor']);
        OnboardingEmailLog::create(['user_id' => $u1->id, 'mail_key' => 'newsletter-cycle-1', 'sent_at' => now()->subDays(3)]);
        OnboardingEmailLog::create(['user_id' => $u2->id, 'mail_key' => 'newsletter-cycle-2', 'sent_at' => now()->subDays(3)]);
        // Outside 30-day window — excluded
        $u3 = User::factory()->create(['role' => 'contributor']);
        OnboardingEmailLog::create(['user_id' => $u3->id, 'mail_key' => 'newsletter-cycle-1', 'sent_at' => now()->subDays(40)]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=nieuwsbrief&range=month');

        $trend = $response->viewData('newsletterTrend');
        $this->assertCount(30, $trend);
        $this->assertEquals(2, collect($trend)->sum('count'));
    }

    public function test_newsletter_trend_quarter_produces_thirteen_weekly_buckets(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=nieuwsbrief&range=quarter');

        $trend = $response->viewData('newsletterTrend');
        $this->assertCount(13, $trend);
    }

    public function test_newsletter_trend_excludes_admin_sends(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $adminRecipient = User::factory()->create(['role' => 'admin']);
        OnboardingEmailLog::create(['user_id' => $adminRecipient->id, 'mail_key' => 'newsletter-cycle-1', 'sent_at' => now()->subDay()]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=nieuwsbrief&range=month');

        $trend = $response->viewData('newsletterTrend');
        $this->assertEquals(0, collect($trend)->sum('count'));
        $this->assertEquals(0, $response->viewData('newsletterStats')['currentSent']);
    }

    public function test_newsletter_trend_excludes_other_mail_keys(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'contributor']);

        OnboardingEmailLog::create(['user_id' => $user->id, 'mail_key' => 'mail_1', 'sent_at' => now()->subDays(2)]);
        OnboardingEmailLog::create(['user_id' => $user->id, 'mail_key' => 'newsletter-cycle-1', 'sent_at' => now()->subDays(2)]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=nieuwsbrief&range=month');

        $trend = $response->viewData('newsletterTrend');
        $this->assertEquals(1, collect($trend)->sum('count'));
    }

    public function test_newsletter_total_subscribers_excludes_admin_unverified_unsubscribed_stub(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Counts
        User::factory()->create(['role' => 'contributor', 'email_verified_at' => now()->subDay()]);
        User::factory()->create(['role' => 'curator', 'email_verified_at' => now()->subDay()]);
        // Excluded: admin
        User::factory()->create(['role' => 'admin', 'email_verified_at' => now()->subDay()]);
        // Excluded: unverified
        User::factory()->unverified()->create(['role' => 'contributor']);
        // Excluded: unsubscribed
        User::factory()->create([
            'role' => 'contributor',
            'email_verified_at' => now()->subDay(),
            'newsletter_unsubscribed_at' => now()->subHour(),
        ]);
        // Excluded: stub email
        User::factory()->create([
            'role' => 'contributor',
            'email' => 'stub@import.hartverwarmers.be',
            'email_verified_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=nieuwsbrief&range=month');

        $stats = $response->viewData('newsletterStats');
        $this->assertEquals(2, $stats['totalSubscribers']);
    }

    public function test_newsletter_stats_month_delta_compares_current_vs_previous(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Current 30-day window: 3 sends
        for ($i = 0; $i < 3; $i++) {
            $u = User::factory()->create(['role' => 'contributor']);
            OnboardingEmailLog::create(['user_id' => $u->id, 'mail_key' => 'newsletter-cycle-1', 'sent_at' => now()->subDays(5 + $i)]);
        }
        // Previous 30-day window: 1 send
        $u4 = User::factory()->create(['role' => 'contributor']);
        OnboardingEmailLog::create(['user_id' => $u4->id, 'mail_key' => 'newsletter-cycle-1', 'sent_at' => now()->subDays(45)]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=nieuwsbrief&range=month');

        $stats = $response->viewData('newsletterStats');
        $this->assertEquals(3, $stats['currentSent']);
        $this->assertEquals(1, $stats['previousSent']);
        $this->assertEquals(2, $stats['delta']);
        $this->assertEquals('deze maand', $stats['rangeLabel']);
    }

    public function test_newsletter_stats_alltime_suppresses_delta(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $u = User::factory()->create(['role' => 'contributor']);
        OnboardingEmailLog::create(['user_id' => $u->id, 'mail_key' => 'newsletter-cycle-1', 'sent_at' => now()->subYear()]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=nieuwsbrief&range=alltime');

        $stats = $response->viewData('newsletterStats');
        $this->assertEquals(1, $stats['currentSent']);
        $this->assertNull($stats['previousSent']);
        $this->assertNull($stats['delta']);
    }

    public function test_unsubscribe_by_cycle_groups_correctly(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Cycle 1 send, user unsubscribed 3 days later → counts
        $u1 = User::factory()->create([
            'role' => 'contributor',
            'newsletter_unsubscribed_at' => now()->subDays(2),
        ]);
        OnboardingEmailLog::create(['user_id' => $u1->id, 'mail_key' => 'newsletter-cycle-1', 'sent_at' => now()->subDays(5)]);

        // Cycle 2 send, no unsubscribe
        $u2 = User::factory()->create(['role' => 'contributor']);
        OnboardingEmailLog::create(['user_id' => $u2->id, 'mail_key' => 'newsletter-cycle-2', 'sent_at' => now()->subDays(5)]);

        // Cycle 5 send, user unsubscribed 1 day later → counts in cycle4plus bucket
        $u3 = User::factory()->create([
            'role' => 'contributor',
            'newsletter_unsubscribed_at' => now()->subDays(4),
        ]);
        OnboardingEmailLog::create(['user_id' => $u3->id, 'mail_key' => 'newsletter-cycle-5', 'sent_at' => now()->subDays(5)]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=nieuwsbrief&range=month');

        $buckets = $response->viewData('unsubscribeByCycle');
        $this->assertEquals(1, $buckets['cycle1']['sent']);
        $this->assertEquals(1, $buckets['cycle1']['unsubscribed']);
        $this->assertEquals(100, $buckets['cycle1']['rate']);

        $this->assertEquals(1, $buckets['cycle2']['sent']);
        $this->assertEquals(0, $buckets['cycle2']['unsubscribed']);
        $this->assertEquals(0, $buckets['cycle2']['rate']);

        $this->assertEquals(0, $buckets['cycle3']['sent']);

        $this->assertEquals(1, $buckets['cycle4plus']['sent']);
        $this->assertEquals(1, $buckets['cycle4plus']['unsubscribed']);
        $this->assertEquals(100, $buckets['cycle4plus']['rate']);
    }

    public function test_unsubscribe_only_counts_within_seven_days_of_send(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Unsubscribed 10 days after send → outside 7-day window, not counted
        $u1 = User::factory()->create([
            'role' => 'contributor',
            'newsletter_unsubscribed_at' => now()->subDays(5),
        ]);
        OnboardingEmailLog::create(['user_id' => $u1->id, 'mail_key' => 'newsletter-cycle-1', 'sent_at' => now()->subDays(15)]);

        // Unsubscribed BEFORE send (impossible in practice, but defensive) → not counted
        $u2 = User::factory()->create([
            'role' => 'contributor',
            'newsletter_unsubscribed_at' => now()->subDays(20),
        ]);
        OnboardingEmailLog::create(['user_id' => $u2->id, 'mail_key' => 'newsletter-cycle-1', 'sent_at' => now()->subDays(10)]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=nieuwsbrief&range=month');

        $buckets = $response->viewData('unsubscribeByCycle');
        $this->assertEquals(2, $buckets['cycle1']['sent']);
        $this->assertEquals(0, $buckets['cycle1']['unsubscribed']);
    }

    public function test_unsubscribe_lowdata_flag_under_five_sends(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $u1 = User::factory()->create(['role' => 'contributor']);
        OnboardingEmailLog::create(['user_id' => $u1->id, 'mail_key' => 'newsletter-cycle-1', 'sent_at' => now()->subDays(2)]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=nieuwsbrief&range=month');

        $buckets = $response->viewData('unsubscribeByCycle');
        $this->assertTrue($buckets['cycle1']['lowData']);
        $this->assertFalse($buckets['cycle2']['lowData']);  // 0 sends — not "lowData", just empty
    }

    public function test_activation_stats_counts_visits_within_seven_days_after_send(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // User visited 3 days after receiving — counts as activated
        $u1 = User::factory()->create([
            'role' => 'contributor',
            'last_visited_at' => now()->subDays(2),
        ]);
        OnboardingEmailLog::create(['user_id' => $u1->id, 'mail_key' => 'newsletter-cycle-1', 'sent_at' => now()->subDays(5)]);

        // User visited but BEFORE the send — not activated
        $u2 = User::factory()->create([
            'role' => 'contributor',
            'last_visited_at' => now()->subDays(10),
        ]);
        OnboardingEmailLog::create(['user_id' => $u2->id, 'mail_key' => 'newsletter-cycle-1', 'sent_at' => now()->subDays(5)]);

        // User never visited — not activated
        $u3 = User::factory()->create(['role' => 'contributor', 'last_visited_at' => null]);
        OnboardingEmailLog::create(['user_id' => $u3->id, 'mail_key' => 'newsletter-cycle-1', 'sent_at' => now()->subDays(5)]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=nieuwsbrief&range=month');

        $stats = $response->viewData('activationStats');
        $this->assertEquals(3, $stats['sent']);
        $this->assertEquals(1, $stats['activated']);
        $this->assertEquals(33, $stats['rate']);
    }

    public function test_activation_stats_visit_outside_seven_day_window_not_counted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Send 20 days ago, visited 5 days ago (15 days after send) — outside 7d window
        $u = User::factory()->create([
            'role' => 'contributor',
            'last_visited_at' => now()->subDays(5),
        ]);
        OnboardingEmailLog::create(['user_id' => $u->id, 'mail_key' => 'newsletter-cycle-1', 'sent_at' => now()->subDays(20)]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=nieuwsbrief&range=month');

        $stats = $response->viewData('activationStats');
        $this->assertEquals(1, $stats['sent']);
        $this->assertEquals(0, $stats['activated']);
    }

    public function test_activation_lowdata_flag_under_five_sends(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        for ($i = 0; $i < 3; $i++) {
            $u = User::factory()->create(['role' => 'contributor']);
            OnboardingEmailLog::create(['user_id' => $u->id, 'mail_key' => 'newsletter-cycle-1', 'sent_at' => now()->subDays(2)]);
        }

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=nieuwsbrief&range=month');

        $stats = $response->viewData('activationStats');
        $this->assertTrue($stats['lowData']);
    }

    public function test_upcoming_sends_buckets_by_cycle(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Cycle 1 in 5 days: created 25 days ago
        User::factory()->create([
            'role' => 'contributor',
            'created_at' => now()->subDays(25)->startOfDay(),
            'email_verified_at' => now()->subDays(25),
        ]);
        // Cycle 2 in 5 days: created 55 days ago
        User::factory()->create([
            'role' => 'contributor',
            'created_at' => now()->subDays(55)->startOfDay(),
            'email_verified_at' => now()->subDays(55),
        ]);
        // Cycle 3 in 5 days: created 85 days ago
        User::factory()->create([
            'role' => 'contributor',
            'created_at' => now()->subDays(85)->startOfDay(),
            'email_verified_at' => now()->subDays(85),
        ]);
        // Cycle 4 in 5 days: created 115 days ago — last_visited recent → passes dormancy gate
        User::factory()->create([
            'role' => 'contributor',
            'created_at' => now()->subDays(115)->startOfDay(),
            'email_verified_at' => now()->subDays(115),
            'last_visited_at' => now()->subDays(10),
        ]);
        // Brand-new signup (day 0) — first 30-day mark falls AT day 30, just outside the
        // [today, today+29] forecast window, so no fire expected.
        User::factory()->create([
            'role' => 'contributor',
            'created_at' => now()->startOfDay(),
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=nieuwsbrief&range=month');

        $forecast = $response->viewData('upcomingNewsletterSends');
        $this->assertEquals(4, $forecast['total']);
        $this->assertEquals(1, $forecast['buckets']['cycle1']['count']);
        $this->assertEquals(1, $forecast['buckets']['cycle2']['count']);
        $this->assertEquals(1, $forecast['buckets']['cycle3']['count']);
        $this->assertEquals(1, $forecast['buckets']['cycle4plus']['count']);
        $this->assertEquals(30, $forecast['windowDays']);
    }

    public function test_upcoming_sends_dormancy_gate_excludes_inactive_cycle4plus(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Cycle 4 in 5 days but last visited 7 months ago — excluded by dormancy gate
        User::factory()->create([
            'role' => 'contributor',
            'created_at' => now()->subDays(115)->startOfDay(),
            'email_verified_at' => now()->subDays(115),
            'last_visited_at' => now()->subMonths(7),
        ]);
        // Cycle 4 in 5 days, last visited 3 months ago — included
        User::factory()->create([
            'role' => 'contributor',
            'created_at' => now()->subDays(115)->startOfDay(),
            'email_verified_at' => now()->subDays(115),
            'last_visited_at' => now()->subMonths(3),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=nieuwsbrief&range=month');

        $forecast = $response->viewData('upcomingNewsletterSends');
        $this->assertEquals(1, $forecast['total']);
        $this->assertEquals(1, $forecast['buckets']['cycle4plus']['count']);
    }

    public function test_upcoming_sends_excludes_unsubscribed_unverified_admin_stub(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // All hit cycle 1 in 5 days, but each excluded for a different reason
        User::factory()->create([
            'role' => 'contributor',
            'created_at' => now()->subDays(25)->startOfDay(),
            'email_verified_at' => now()->subDays(25),
            'newsletter_unsubscribed_at' => now()->subDay(),
        ]);
        User::factory()->unverified()->create([
            'role' => 'contributor',
            'created_at' => now()->subDays(25)->startOfDay(),
        ]);
        User::factory()->create([
            'role' => 'admin',
            'created_at' => now()->subDays(25)->startOfDay(),
            'email_verified_at' => now()->subDays(25),
        ]);
        User::factory()->create([
            'role' => 'contributor',
            'email' => 'stub@import.hartverwarmers.be',
            'created_at' => now()->subDays(25)->startOfDay(),
            'email_verified_at' => now()->subDays(25),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=nieuwsbrief&range=month');

        $forecast = $response->viewData('upcomingNewsletterSends');
        $this->assertEquals(0, $forecast['total']);
    }

    public function test_thank_stats_quarter_delta_compares_current_vs_previous(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $now = now();

        // Previous quarter window: 4 downloads, 1 thanked → 25%.
        // Place comfortably inside the previous window (around 16-18 weeks ago).
        for ($i = 0; $i < 4; $i++) {
            $u = User::factory()->create();
            $f = Fiche::factory()->published()->create();
            UserInteraction::create([
                'user_id' => $u->id, 'interactable_type' => Fiche::class, 'interactable_id' => $f->id,
                'type' => 'download', 'created_at' => $now->copy()->subWeeks(16 + ($i % 3)),
            ]);
            if ($i === 0) {
                Like::create([
                    'user_id' => $u->id, 'likeable_type' => Fiche::class, 'likeable_id' => $f->id,
                    'type' => 'kudos', 'count' => 1, 'created_at' => $now->copy()->subWeeks(15),
                ]);
            }
        }

        // Current quarter window: 2 downloads, 1 thanked → 50%.
        // Place comfortably inside the current window (around 4-6 weeks ago).
        for ($i = 0; $i < 2; $i++) {
            $u = User::factory()->create();
            $f = Fiche::factory()->published()->create();
            UserInteraction::create([
                'user_id' => $u->id, 'interactable_type' => Fiche::class, 'interactable_id' => $f->id,
                'type' => 'download', 'created_at' => $now->copy()->subWeeks(4 + $i),
            ]);
            if ($i === 0) {
                Like::create([
                    'user_id' => $u->id, 'likeable_type' => Fiche::class, 'likeable_id' => $f->id,
                    'type' => 'kudos', 'count' => 1, 'created_at' => $now->copy()->subWeeks(3),
                ]);
            }
        }

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=bedankjes&range=quarter');

        $stats = $response->viewData('thankStats');
        $this->assertEquals(50, $stats['currentRate']);
        $this->assertEquals(25, $stats['previousRate']);
        $this->assertEquals(25, $stats['delta']);
    }

    public function test_overzicht_renders_four_objective_stat_cards_with_range_preserving_links(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.dashboard', ['tab' => 'overzicht', 'range' => 'quarter']));

        $response->assertOk();
        $response->assertSee('Objectieven');
        $response->assertSee('Initiatieven');
        foreach (['Fichekwaliteit', 'Activatie', 'Interactie', 'Retentie'] as $title) {
            $response->assertSee($title);
        }
        foreach (['presentatiekwaliteit', 'onboarding', 'bedankjes', 'nieuwsbrief'] as $slug) {
            $response->assertSee('?tab='.$slug.'&amp;range=quarter', escape: false);
        }

        // Initiatives render as compact rows that deep-link into their objective tab…
        $response->assertSee('Bedankflow na download');
        $response->assertSee('?tab=bedankjes&amp;init=bedankflow-na-download', escape: false);
        // …and NOT as the old per-KR detail cards (these exact strings live only
        // in okr-kr-impact.blade.php, which the overview no longer renders).
        $response->assertDontSee('sinds de start');
        $response->assertDontSee('Nog geen meting beschikbaar.');
    }
}
