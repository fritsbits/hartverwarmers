<?php

namespace Tests\Feature\Admin;

use App\Models\Fiche;
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
    }

    public function test_weekly_trend_groups_by_quality_assessed_at(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Two fiches assessed in different weeks
        Fiche::factory()->published()->withPresentationScore(40)->create([
            'quality_assessed_at' => now()->subWeeks(2)->startOfWeek(),
        ]);
        Fiche::factory()->published()->withPresentationScore(80)->create([
            'quality_assessed_at' => now()->startOfWeek(),
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

    public function test_trend_delta_hidden_when_fewer_than_two_weeks(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Fiche::factory()->published()->withPresentationScore(60)->create([
            'quality_assessed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertNull($response->viewData('trendDelta'));
    }

    public function test_trend_delta_is_last_minus_first_week(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Fiche::factory()->published()->withPresentationScore(30)->create([
            'quality_assessed_at' => now()->subWeeks(3)->startOfWeek(),
        ]);
        Fiche::factory()->published()->withPresentationScore(70)->create([
            'quality_assessed_at' => now()->startOfWeek(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

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

    public function test_last_five_fiches_ordered_by_quality_assessed_at(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create 6 fiches so we can confirm only 5 returned
        for ($i = 1; $i <= 6; $i++) {
            Fiche::factory()->published()->withPresentationScore($i * 10)->create([
                'quality_assessed_at' => now()->subDays(7 - $i),
            ]);
        }
        // Unpublished — excluded
        Fiche::factory()->withPresentationScore(99)->create(['quality_assessed_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $fiches = $response->viewData('lastFiches');
        $this->assertCount(5, $fiches);
        // Most recent first: score 60 (day 1), 50, 40, 30, 20
        $this->assertEquals(60, $fiches->first()->presentation_score);
    }

    public function test_last_five_average_vs_global_average(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // 3 old fiches score 20 each, 5 recent score 80 each
        for ($i = 0; $i < 3; $i++) {
            Fiche::factory()->published()->withPresentationScore(20)->create([
                'quality_assessed_at' => now()->subMonths(2),
            ]);
        }
        for ($i = 0; $i < 5; $i++) {
            Fiche::factory()->published()->withPresentationScore(80)->create([
                'quality_assessed_at' => now()->subDays($i),
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
}
