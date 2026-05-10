<?php

namespace Tests\Feature\Admin;

use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Like;
use App\Models\OnboardingEmailLog;
use App\Models\User;
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
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Presentatiekwaliteit');
        $response->assertSee('Suggestie-adoptie');
    }

    public function test_default_range_is_week(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertEquals('week', $response->viewData('range'));
        // Week range builds 7 daily slots
        $this->assertCount(7, $response->viewData('weeklyTrend'));
    }

    public function test_month_range_builds_four_weekly_slots(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?range=month');

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

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

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

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?range=month');

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

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?range=month');

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

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

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

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

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

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

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

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        // Denominator = 2 (fiches with non-empty suggestions), numerator = 1
        $this->assertEquals(2, $response->viewData('withSuggestions'));
        $this->assertEquals(1, $response->viewData('withAnyApplied'));
        $this->assertEquals(50, $response->viewData('adoptionRate'));
    }

    public function test_adoption_rate_is_zero_when_no_suggestions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

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

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

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

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

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

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

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

    public function test_aanmeldingen_tab_is_accessible(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen');

        $response->assertOk();
        $response->assertSee('Aanmeldingen');
        $this->assertEquals('aanmeldingen', $response->viewData('tab'));
    }

    public function test_aanmeldingen_tab_defaults_to_month_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen');

        $response->assertOk();
        $this->assertEquals('month', $response->viewData('range'));
    }

    public function test_aanmeldingen_tab_invalid_range_falls_back_to_month(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=garbage');

        $response->assertOk();
        $this->assertEquals('month', $response->viewData('range'));
    }

    public function test_aanmeldingen_tab_accepts_quarter_and_alltime(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $quarter = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=quarter');
        $this->assertEquals('quarter', $quarter->viewData('range'));

        $alltime = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=alltime');
        $this->assertEquals('alltime', $alltime->viewData('range'));
    }

    public function test_presentatiekwaliteit_default_range_unchanged(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertEquals('week', $response->viewData('range'));
    }

    public function test_invalid_tab_falls_back_to_presentatiekwaliteit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=garbage');

        $response->assertOk();
        $this->assertEquals('presentatiekwaliteit', $response->viewData('tab'));
    }

    public function test_signup_trend_month_produces_30_daily_buckets(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->create(['role' => 'contributor', 'created_at' => now()->subDays(2)]);
        User::factory()->create(['role' => 'contributor', 'created_at' => now()->subDays(2)]);
        User::factory()->create(['role' => 'contributor', 'created_at' => now()]);
        // Outside 30-day window — excluded
        User::factory()->create(['role' => 'contributor', 'created_at' => now()->subDays(40)]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=month');

        $trend = $response->viewData('signupTrend');
        $this->assertCount(30, $trend);
        $this->assertEquals(2, collect($trend)->firstWhere('key', now()->subDays(2)->format('Y-m-d'))['count']);
        $this->assertEquals(1, collect($trend)->firstWhere('key', now()->format('Y-m-d'))['count']);
        $this->assertEquals(3, collect($trend)->sum('count'));
    }

    public function test_signup_trend_quarter_produces_13_weekly_buckets(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->create(['role' => 'contributor', 'created_at' => now()->subWeeks(2)]);
        User::factory()->create(['role' => 'contributor', 'created_at' => now()]);
        // Outside 90-day window
        User::factory()->create(['role' => 'contributor', 'created_at' => now()->subDays(100)]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=quarter');

        $trend = $response->viewData('signupTrend');
        $this->assertCount(13, $trend);
        $this->assertEquals(2, collect($trend)->sum('count'));
    }

    public function test_signup_trend_alltime_starts_at_earliest_signup(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->create(['role' => 'contributor', 'created_at' => now()->subMonths(2)->startOfMonth()->addDays(5)]);
        User::factory()->create(['role' => 'contributor', 'created_at' => now()->subMonth()->startOfMonth()->addDays(5)]);
        User::factory()->create(['role' => 'contributor', 'created_at' => now()->startOfMonth()->addDays(2)]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=alltime');

        $trend = $response->viewData('signupTrend');
        // 3 monthly buckets: subMonths(2), subMonth(1), current
        $this->assertCount(3, $trend);
        $this->assertEquals(1, $trend[0]['count']);
        $this->assertEquals(1, $trend[1]['count']);
        $this->assertEquals(1, $trend[2]['count']);
    }

    public function test_signup_trend_excludes_admins_and_stub_emails(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Admin — excluded
        User::factory()->create(['role' => 'admin', 'created_at' => now()]);
        // Stub import email — excluded
        User::factory()->create([
            'role' => 'contributor',
            'email' => 'someone@import.hartverwarmers.be',
            'created_at' => now(),
        ]);
        // Soft-deleted — excluded
        $deleted = User::factory()->create(['role' => 'contributor', 'created_at' => now()]);
        $deleted->delete();
        // Real contributor — counts
        User::factory()->create(['role' => 'contributor', 'created_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=month');

        $trend = $response->viewData('signupTrend');
        $this->assertEquals(1, collect($trend)->sum('count'));
    }

    public function test_signup_trend_alltime_returns_empty_when_no_signups(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        // Admin only — no real signups exist
        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=alltime');

        $trend = $response->viewData('signupTrend');
        $this->assertEmpty($trend);
    }

    public function test_signup_trend_month_includes_signup_at_oldest_bucket_boundary(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Signup at the start of the oldest bucket — must be counted
        User::factory()->create([
            'role' => 'contributor',
            'created_at' => now()->subDays(29)->startOfDay()->addMinutes(5),
        ]);
        // Signup just outside the window — must NOT be counted
        User::factory()->create([
            'role' => 'contributor',
            'created_at' => now()->subDays(29)->startOfDay()->subMinutes(5),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=month');

        $trend = $response->viewData('signupTrend');
        $this->assertEquals(1, collect($trend)->sum('count'));
    }

    public function test_signup_stats_month_current_count_and_delta(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Current 30-day window: 3 signups
        User::factory()->count(3)->create(['role' => 'contributor', 'created_at' => now()->subDays(5)]);
        // Previous 30-day window: 1 signup
        User::factory()->create(['role' => 'contributor', 'created_at' => now()->subDays(45)]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=month');

        $stats = $response->viewData('signupStats');
        $this->assertEquals(3, $stats['currentCount']);
        $this->assertEquals(1, $stats['previousCount']);
        $this->assertEquals(2, $stats['delta']);
        $this->assertEquals('deze maand', $stats['rangeLabel']);
    }

    public function test_signup_stats_quarter_current_and_delta(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Current 90-day window
        User::factory()->count(2)->create(['role' => 'contributor', 'created_at' => now()->subDays(10)]);
        // Previous 90-day window (90–180 days ago)
        User::factory()->count(5)->create(['role' => 'contributor', 'created_at' => now()->subDays(120)]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=quarter');

        $stats = $response->viewData('signupStats');
        $this->assertEquals(2, $stats['currentCount']);
        $this->assertEquals(5, $stats['previousCount']);
        $this->assertEquals(-3, $stats['delta']);
        $this->assertEquals('deze 3 maanden', $stats['rangeLabel']);
    }

    public function test_signup_stats_alltime_suppresses_delta(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->count(4)->create(['role' => 'contributor', 'created_at' => now()->subMonths(3)]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=alltime');

        $stats = $response->viewData('signupStats');
        $this->assertEquals(4, $stats['currentCount']);
        $this->assertNull($stats['previousCount']);
        $this->assertNull($stats['delta']);
        $this->assertEquals('sinds start', $stats['rangeLabel']);
    }

    public function test_signup_stats_excludes_admins_and_stubs_in_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->create(['role' => 'contributor', 'created_at' => now()]);
        User::factory()->create(['role' => 'admin', 'created_at' => now()]);
        User::factory()->create([
            'role' => 'contributor',
            'email' => 'stub@import.hartverwarmers.be',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=month');

        $stats = $response->viewData('signupStats');
        $this->assertEquals(1, $stats['currentCount']);
    }

    public function test_signup_stats_month_boundary_between_current_and_previous(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // At the exact start of the current 30-day window — counts as current
        User::factory()->create([
            'role' => 'contributor',
            'created_at' => now()->subDays(29)->startOfDay()->addMinutes(5),
        ]);
        // 5 minutes before the current window starts — counts as previous
        User::factory()->create([
            'role' => 'contributor',
            'created_at' => now()->subDays(29)->startOfDay()->subMinutes(5),
        ]);
        // At the exact start of the previous 30-day window — counts as previous
        User::factory()->create([
            'role' => 'contributor',
            'created_at' => now()->subDays(59)->startOfDay()->addMinutes(5),
        ]);
        // Outside the previous window — counts as neither
        User::factory()->create([
            'role' => 'contributor',
            'created_at' => now()->subDays(59)->startOfDay()->subMinutes(5),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=month');

        $stats = $response->viewData('signupStats');
        $this->assertEquals(1, $stats['currentCount']);
        $this->assertEquals(2, $stats['previousCount']);
        $this->assertEquals(-1, $stats['delta']);
    }

    public function test_verification_rate_in_month_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // 6 in cohort: 4 verified, 2 unverified
        User::factory()->count(4)->create([
            'role' => 'contributor',
            'created_at' => now()->subDays(5),
            'email_verified_at' => now()->subDays(4),
        ]);
        User::factory()->count(2)->unverified()->create([
            'role' => 'contributor',
            'created_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=month');

        $stats = $response->viewData('signupStats');
        $this->assertEquals(6, $stats['cohortCount']);
        $this->assertEquals(4, $stats['verifiedCount']);
        $this->assertEquals(67, $stats['verificationRate']);
        $this->assertFalse($stats['verificationLowData']);
    }

    public function test_verification_rate_zero_when_cohort_empty(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=month');

        $stats = $response->viewData('signupStats');
        $this->assertEquals(0, $stats['cohortCount']);
        $this->assertEquals(0, $stats['verifiedCount']);
        $this->assertEquals(0, $stats['verificationRate']);
    }

    public function test_verification_rate_low_data_flag_when_cohort_under_5(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->count(3)->create([
            'role' => 'contributor',
            'created_at' => now()->subDays(2),
            'email_verified_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=month');

        $stats = $response->viewData('signupStats');
        $this->assertEquals(3, $stats['cohortCount']);
        $this->assertTrue($stats['verificationLowData']);
    }

    public function test_verification_rate_alltime_uses_all_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->count(2)->create([
            'role' => 'contributor',
            'created_at' => now()->subYear(),
            'email_verified_at' => now()->subYear(),
        ]);
        User::factory()->count(1)->unverified()->create([
            'role' => 'contributor',
            'created_at' => now()->subYear(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=alltime');

        $stats = $response->viewData('signupStats');
        $this->assertEquals(3, $stats['cohortCount']);
        $this->assertEquals(2, $stats['verifiedCount']);
        $this->assertEquals(67, $stats['verificationRate']);
    }

    public function test_total_members_count_includes_all_non_admin_non_stub(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->count(3)->create(['role' => 'contributor']);
        User::factory()->count(1)->create(['role' => 'curator']);
        // Excluded
        User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'contributor', 'email' => 'a@import.hartverwarmers.be']);
        $deleted = User::factory()->create(['role' => 'contributor']);
        $deleted->delete();

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=month');

        $stats = $response->viewData('signupStats');
        $this->assertEquals(4, $stats['totalMembers']);
    }

    public function test_total_members_count_unaffected_by_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->create(['role' => 'contributor', 'created_at' => now()->subYears(2)]);
        User::factory()->create(['role' => 'contributor', 'created_at' => now()]);

        $month = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=month');
        $alltime = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=alltime');

        $this->assertEquals(2, $month->viewData('signupStats')['totalMembers']);
        $this->assertEquals(2, $alltime->viewData('signupStats')['totalMembers']);
    }

    public function test_aanmeldingen_partial_shows_signup_count_and_total_members(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->count(3)->create(['role' => 'contributor', 'created_at' => now()->subDays(2)]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=month');

        $response->assertOk();
        $response->assertSee('Aanmeldingen');
        $response->assertSee('E-mailverificatie');
        $response->assertSee('totaal leden');
        $response->assertSee('deze maand');
    }

    public function test_aanmeldingen_empty_state_when_no_signups_in_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard').'?tab=aanmeldingen&range=month');

        $response->assertOk();
        $response->assertSee('Nog geen aanmeldingen');
    }
}
