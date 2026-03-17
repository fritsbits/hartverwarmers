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

    public function test_overview_shows_warning_when_no_fiche_of_month_for_current_month(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.fiches.index'));

        $response->assertSee('Geen fiche van de maand');
    }

    public function test_overview_hides_warning_when_fiche_of_month_exists(): void
    {
        $admin = $this->createAdmin();
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()
            ->for($initiative)
            ->for(User::factory())
            ->published()
            ->ficheOfMonth(now()->format('Y-m'))
            ->create();

        $response = $this->actingAs($admin)->get(route('admin.fiches.index'));

        $response->assertDontSee('Geen fiche van de maand');
    }

    public function test_admin_can_set_fiche_of_month(): void
    {
        $admin = $this->createAdmin();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()
            ->for($initiative)
            ->for(User::factory())
            ->published()
            ->create();

        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Livewire\AdminFicheOverview::class)
            ->call('setFicheOfMonth', $fiche->id, now()->format('Y-m'));

        $this->assertEquals(now()->format('Y-m'), $fiche->fresh()->featured_month);
    }

    public function test_setting_fiche_of_month_unsets_existing_for_same_month(): void
    {
        $admin = $this->createAdmin();
        $initiative = Initiative::factory()->published()->create();
        $month = now()->format('Y-m');

        $existing = Fiche::factory()
            ->for($initiative)
            ->for(User::factory())
            ->published()
            ->ficheOfMonth($month)
            ->create();

        $newFiche = Fiche::factory()
            ->for($initiative)
            ->for(User::factory())
            ->published()
            ->create();

        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Livewire\AdminFicheOverview::class)
            ->call('setFicheOfMonth', $newFiche->id, $month);

        $this->assertNull($existing->fresh()->featured_month);
        $this->assertEquals($month, $newFiche->fresh()->featured_month);
    }
}
