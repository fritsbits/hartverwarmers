<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuratorUserAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_curator_can_view_user_list(): void
    {
        $curator = User::factory()->curator()->create();

        $this->actingAs($curator)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_contributor_cannot_view_user_list(): void
    {
        $contributor = User::factory()->create(['role' => 'contributor']);

        $this->actingAs($contributor)
            ->get(route('admin.users.index'))
            ->assertStatus(403);
    }

    public function test_curator_does_not_see_impersonate_button(): void
    {
        $curator = User::factory()->curator()->create();
        User::factory()->create();

        $this->actingAs($curator)
            ->get(route('admin.users.index'))
            ->assertDontSee('Nabootsen');
    }

    public function test_admin_sees_impersonate_button(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertSee('Nabootsen');
    }

    public function test_curator_cannot_start_impersonation(): void
    {
        $curator = User::factory()->curator()->create();
        $target = User::factory()->create();

        $this->actingAs($curator)
            ->post(route('admin.impersonate.start', $target))
            ->assertStatus(403);
    }

    public function test_curator_sees_curatie_sidebar_section_but_not_platform(): void
    {
        $curator = User::factory()->curator()->create();

        $this->actingAs($curator)
            ->get(route('admin.users.index'))
            ->assertSee('Curatie')
            ->assertDontSee('Platform')
            ->assertDontSee('Dashboard');
    }

    public function test_admin_sees_curatie_and_platform_sidebar_sections(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertSee('Curatie')
            ->assertSee('Platform');
    }

    public function test_contributor_sees_neither_curatie_nor_platform(): void
    {
        $contributor = User::factory()->create(['role' => 'contributor']);

        $this->actingAs($contributor)
            ->get(route('profile.show'))
            ->assertDontSee('Curatie')
            ->assertDontSee('Platform');
    }
}
