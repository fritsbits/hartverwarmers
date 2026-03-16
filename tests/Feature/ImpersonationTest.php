<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_start_impersonation(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.impersonate.start', $target));

        $response->assertRedirect();
        $this->assertAuthenticatedAs($target);
        $this->assertEquals($admin->id, session('original_user_id'));
    }

    public function test_non_admin_cannot_start_impersonation(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.impersonate.start', $target));

        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_when_starting_impersonation(): void
    {
        $target = User::factory()->create();

        $response = $this->post(route('admin.impersonate.start', $target));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_cannot_impersonate_self(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.impersonate.start', $admin));

        $response->assertStatus(403);
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_cannot_nest_impersonation(): void
    {
        $admin = User::factory()->admin()->create();
        $target1 = User::factory()->create();
        $target2 = User::factory()->create();

        $this->actingAs($admin)
            ->withSession(['original_user_id' => $admin->id])
            ->post(route('admin.impersonate.start', $target2))
            ->assertStatus(403);
    }

    public function test_admin_cannot_impersonate_trashed_user(): void
    {
        $admin = User::factory()->admin()->create();
        $trashed = User::factory()->create();
        $trashed->delete();

        $response = $this->actingAs($admin)->post(route('admin.impersonate.start', $trashed));

        $response->assertStatus(404);
    }

    public function test_admin_can_stop_impersonation(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        // Start impersonation
        $this->actingAs($admin)->post(route('admin.impersonate.start', $target));

        // Stop impersonation
        $response = $this->post(route('admin.impersonate.stop'));

        $response->assertRedirect();
        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('original_user_id'));
    }

    public function test_stop_without_impersonation_returns_403(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.impersonate.stop'));

        $response->assertStatus(403);
    }

    public function test_logout_while_impersonating_ends_session(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.impersonate.start', $target));
        $this->post(route('logout'));

        $this->assertGuest();
    }

    public function test_admin_can_impersonate_another_admin(): void
    {
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();

        $response = $this->actingAs($admin1)->post(route('admin.impersonate.start', $admin2));

        $response->assertRedirect();
        $this->assertAuthenticatedAs($admin2);
        $this->assertEquals($admin1->id, session('original_user_id'));
    }
}
