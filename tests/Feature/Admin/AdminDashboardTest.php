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
}
