<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $response->assertSee('Queue');
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
        $response->assertSee('Heartbeat');
        $response->assertSee('Wachtend');
        $response->assertSee('Mislukt');
    }
}
