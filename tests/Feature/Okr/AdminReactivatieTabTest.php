<?php

namespace Tests\Feature\Okr;

use App\Models\User;
use Database\Seeders\OkrSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReactivatieTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_reactivatie_tab_renders_kr_and_initiative(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['tab' => 'reactivatie']));

        $response->assertOk();
        $response->assertSee('Slapers terug actief');   // KR-label
        $response->assertSee('Reactivatie-campagne');    // initiatief-label
    }

    public function test_reactivatie_tab_button_appears_in_navigation(): void
    {
        $this->seed(OkrSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Reactivatie'); // tab-knop (auto uit $objectives)
    }
}
