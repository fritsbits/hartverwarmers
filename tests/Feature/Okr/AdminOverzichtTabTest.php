<?php

namespace Tests\Feature\Okr;

use App\Models\User;
use Database\Seeders\OkrSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOverzichtTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_overzicht_is_default_tab_for_admin(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Presentatiekwaliteit');
        $response->assertSee('Onboarding');
        $response->assertSee('Bedankjes');
        $response->assertSee('Nieuwsbrief');
    }

    public function test_overzicht_renders_each_objective_as_link_card(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['tab' => 'overzicht']));

        $response->assertOk();
        $response->assertSee('?tab=presentatiekwaliteit', false);
        $response->assertSee('?tab=onboarding', false);
        $response->assertSee('?tab=bedankjes', false);
        $response->assertSee('?tab=nieuwsbrief', false);
    }

    public function test_unknown_tab_falls_back_to_overzicht(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['tab' => 'nonexistent']));

        $response->assertOk();
        $response->assertSee('Presentatiekwaliteit');  // overzicht renders all 4 objectives
    }
}
