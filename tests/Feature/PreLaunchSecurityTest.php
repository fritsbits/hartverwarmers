<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreLaunchSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_error_preview_routes_are_removed(): void
    {
        $this->get('/preview/404')->assertStatus(404);
        $this->get('/preview/403')->assertStatus(404);
        $this->get('/preview/500')->assertStatus(404);
    }

    public function test_impersonation_middleware_blocks_admin_routes(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.impersonate.start', $target));

        $this->get(route('admin.design-system'))->assertStatus(403);
        $this->get(route('admin.users.index'))->assertStatus(403);
        $this->get(route('admin.mails'))->assertStatus(403);
    }

    public function test_impersonation_middleware_allows_stop_route(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.impersonate.start', $target));

        $this->post(route('admin.impersonate.stop'))->assertRedirect();
    }
}
