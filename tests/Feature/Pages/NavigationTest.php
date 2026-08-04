<?php

namespace Tests\Feature\Pages;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_login_button_is_hidden_on_xs_to_avoid_horizontal_overflow(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('hidden sm:inline-flex', false);
        $response->assertSee(route('login'));
        $response->assertSee(route('register'));
    }

    public function test_guest_mobile_menu_exposes_login_link_on_xs(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('class="sm:hidden flex items-center gap-2 px-3 py-2 rounded-lg text-base font-medium text-[var(--color-text-primary)] hover:bg-[var(--color-bg-accent-light)]"', false);
    }

    public function test_authenticated_user_does_not_see_guest_mobile_login_link(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('class="sm:hidden flex items-center gap-2 px-3 py-2 rounded-lg text-base font-medium text-[var(--color-text-primary)] hover:bg-[var(--color-bg-accent-light)]"', false);
    }
}
