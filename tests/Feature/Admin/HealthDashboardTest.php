<?php

namespace Tests\Feature\Admin;

use App\Console\Commands\CheckThemesHealth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HealthDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $response = $this->get(route('admin.health'));

        $response->assertRedirect(route('login'));
    }

    public function test_contributor_gets_403(): void
    {
        $user = User::factory()->create(['role' => 'contributor']);

        $response = $this->actingAs($user)->get(route('admin.health'));

        $response->assertForbidden();
    }

    public function test_curator_gets_403(): void
    {
        $user = User::factory()->create(['role' => 'curator']);

        $response = $this->actingAs($user)->get(route('admin.health'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_health_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('admin.health'));

        $response->assertOk();
        $response->assertSee('Gezondheid');
        $response->assertSee('Server');
        $response->assertSee('Wachtrij');
    }

    public function test_dashboard_shows_disk_info(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('admin.health'));

        $response->assertOk();
        $response->assertSee('Schijf');
    }

    public function test_dashboard_shows_queue_health(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('admin.health'));

        $response->assertOk();
        $response->assertSee('Achtergrondtaken');
        $response->assertSee('Wachtend');
        $response->assertSee('Mislukt');
    }

    public function test_dashboard_says_theme_check_never_ran_when_snapshot_is_missing(): void
    {
        Cache::forget(CheckThemesHealth::SNAPSHOT_KEY);
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('admin.health'));

        $response->assertOk();
        $response->assertSee('Themakalender');
        $response->assertSee('nog nooit gedraaid');
    }

    public function test_dashboard_shows_theme_check_snapshot(): void
    {
        Cache::forever(CheckThemesHealth::SNAPSHOT_KEY, [
            'checked_at' => now()->subHours(3)->toIso8601String(),
            'horizon_days' => 121,
            'horizon_date' => '2026-12-31',
            'empty_upcoming' => ['dag-van-de-verzorgenden'],
            'drift' => ['count' => 2, 'summary' => '2 koppeling(en) alleen in het bestand, 0 alleen in de databank'],
            'watermark' => '2026-09-01',
            'fiches_after_watermark' => 7,
            'exceeded' => ['empty_upcoming', 'drift'],
        ]);
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('admin.health'));

        $response->assertOk();
        $response->assertSee('121 dagen');
        $response->assertSee('dag-van-de-verzorgenden');
        $response->assertSee('2 koppeling(en) alleen in het bestand');
        $response->assertSee('7');
        $response->assertSee('3 uur geleden');
        $response->assertDontSee('nog nooit gedraaid');
    }
}
