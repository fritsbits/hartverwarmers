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
}
