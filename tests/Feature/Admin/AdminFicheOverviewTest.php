<?php

namespace Tests\Feature\Admin;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFicheOverviewTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_can_access_fiche_overview(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.fiches.index'));

        $response->assertOk();
        $response->assertSeeLivewire('admin-fiche-overview');
    }

    public function test_non_admin_cannot_access_fiche_overview(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/fiches');

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_fiche_overview(): void
    {
        $response = $this->get('/admin/fiches');

        $response->assertRedirect('/login');
    }

    public function test_overview_shows_published_fiches(): void
    {
        $admin = $this->createAdmin();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()
            ->for($initiative)
            ->for(User::factory())
            ->published()
            ->create(['title' => 'Muzikale herinneringen']);

        $response = $this->actingAs($admin)->get(route('admin.fiches.index'));

        $response->assertSee('Muzikale herinneringen');
    }
}
