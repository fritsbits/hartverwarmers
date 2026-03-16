<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $response = $this->get(route('admin.design-system'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_users_get_403(): void
    {
        $user = User::factory()->create(['role' => 'contributor']);

        $response = $this->actingAs($user)->get(route('admin.design-system'));

        $response->assertForbidden();
    }

    public function test_admin_users_can_view_design_system(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('admin.design-system'));

        $response->assertOk();
    }

    public function test_page_contains_all_section_headings(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('admin.design-system'));

        $response->assertOk();
        $response->assertSee('Kleuren');
        $response->assertSee('Typografie');
        $response->assertSee('Knoppen & Links', false);
        $response->assertSee('Badges & Tags', false);
        $response->assertSee('Kaarten');
        $response->assertSee('Layout Patronen');
        $response->assertSee('Formulieren');
        $response->assertSee('Interactief');
        $response->assertSee('Hulpmiddelen');
    }

    public function test_page_renders_mock_initiative_data(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('admin.design-system'));

        $response->assertOk();
        $response->assertSee('Muziekbingo voor bewoners');
        $response->assertSee('Maria Janssen');
    }

    public function test_page_renders_diamant_gems(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('admin.design-system'));

        $response->assertOk();
        $response->assertSee('diamant-gem', false);
        $response->assertSee('viewBox="0 0 100 100"', false);
    }
}
