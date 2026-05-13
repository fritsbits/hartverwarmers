<?php

namespace Tests\Feature\Admin;

use App\Livewire\AdminFicheOverview;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
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

    public function test_quadrant_strong_filters_high_quality_high_presentation(): void
    {
        $admin = $this->createAdmin();
        $initiative = Initiative::factory()->published()->create();

        // Q>=50, P>=50 — should appear
        Fiche::factory()
            ->for($initiative)->for(User::factory())->published()
            ->withScores(quality: 70)->withPresentationScore(60)
            ->create(['title' => 'Topactiviteit zxq001']);

        // Q>=50, P<50 — should NOT appear
        Fiche::factory()
            ->for($initiative)->for(User::factory())->published()
            ->withScores(quality: 70)->withPresentationScore(30)
            ->create(['title' => 'Verbeterbaar zxq002']);

        $this->actingAs($admin)
            ->get(route('admin.fiches.index', ['status' => 'q-strong']))
            ->assertSee('Topactiviteit zxq001')
            ->assertDontSee('Verbeterbaar zxq002');
    }

    public function test_quadrant_quickwin_filters_high_quality_low_presentation(): void
    {
        $admin = $this->createAdmin();
        $initiative = Initiative::factory()->published()->create();

        Fiche::factory()
            ->for($initiative)->for(User::factory())->published()
            ->withScores(quality: 60)->withPresentationScore(30)
            ->create(['title' => 'Quickwin activiteit zxq003']);

        Fiche::factory()
            ->for($initiative)->for(User::factory())->published()
            ->withScores(quality: 60)->withPresentationScore(60)
            ->create(['title' => 'Topactiviteit zxq004']);

        $this->actingAs($admin)
            ->get(route('admin.fiches.index', ['status' => 'q-quickwin']))
            ->assertSee('Quickwin activiteit zxq003')
            ->assertDontSee('Topactiviteit zxq004');
    }

    public function test_quadrant_wellwritten_filters_low_quality_high_presentation(): void
    {
        $admin = $this->createAdmin();
        $initiative = Initiative::factory()->published()->create();

        Fiche::factory()
            ->for($initiative)->for(User::factory())->published()
            ->withScores(quality: 30)->withPresentationScore(60)
            ->create(['title' => 'Mooi geschreven zxq005']);

        Fiche::factory()
            ->for($initiative)->for(User::factory())->published()
            ->withScores(quality: 60)->withPresentationScore(60)
            ->create(['title' => 'Topactiviteit zxq006']);

        $this->actingAs($admin)
            ->get(route('admin.fiches.index', ['status' => 'q-wellwritten']))
            ->assertSee('Mooi geschreven zxq005')
            ->assertDontSee('Topactiviteit zxq006');
    }

    public function test_quadrant_needswork_filters_low_quality_low_presentation(): void
    {
        $admin = $this->createAdmin();
        $initiative = Initiative::factory()->published()->create();

        Fiche::factory()
            ->for($initiative)->for(User::factory())->published()
            ->withScores(quality: 30)->withPresentationScore(30)
            ->create(['title' => 'Zwakke activiteit zxq007']);

        Fiche::factory()
            ->for($initiative)->for(User::factory())->published()
            ->withScores(quality: 60)->withPresentationScore(60)
            ->create(['title' => 'Topactiviteit zxq008']);

        $this->actingAs($admin)
            ->get(route('admin.fiches.index', ['status' => 'q-needswork']))
            ->assertSee('Zwakke activiteit zxq007')
            ->assertDontSee('Topactiviteit zxq008');
    }

    public function test_quadrant_threshold_boundary_at_exactly_50(): void
    {
        $admin = $this->createAdmin();
        $initiative = Initiative::factory()->published()->create();

        // Exactly 50 on both — should be in q-strong (>= 50)
        Fiche::factory()
            ->for($initiative)->for(User::factory())->published()
            ->withScores(quality: 50)->withPresentationScore(50)
            ->create(['title' => 'Grensgeval zxq009']);

        // 49 on quality — should NOT be in q-strong
        Fiche::factory()
            ->for($initiative)->for(User::factory())->published()
            ->withScores(quality: 49)->withPresentationScore(50)
            ->create(['title' => 'Net eronder zxq010']);

        $this->actingAs($admin)
            ->get(route('admin.fiches.index', ['status' => 'q-strong']))
            ->assertSee('Grensgeval zxq009')
            ->assertDontSee('Net eronder zxq010');
    }

    public function test_livewire_toggle_diamond_sets_and_clears_awarded_at(): void
    {
        $admin = $this->createAdmin();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->for($initiative)->for(User::factory())->published()->create([
            'has_diamond' => false,
            'diamond_awarded_at' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(AdminFicheOverview::class)
            ->call('toggleDiamond', $fiche->id);

        $fiche->refresh();
        $this->assertTrue($fiche->has_diamond);
        $this->assertNotNull($fiche->diamond_awarded_at);

        Livewire::actingAs($admin)
            ->test(AdminFicheOverview::class)
            ->call('toggleDiamond', $fiche->id);

        $fiche->refresh();
        $this->assertFalse($fiche->has_diamond);
        $this->assertNull($fiche->diamond_awarded_at);
    }

    public function test_livewire_toggle_diamond_invalidates_homepage_cache(): void
    {
        $admin = $this->createAdmin();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->for($initiative)->for(User::factory())->published()->create([
            'has_diamond' => false,
        ]);

        Cache::put('home:recent-diamond', 'stale', now()->addMinutes(5));

        Livewire::actingAs($admin)
            ->test(AdminFicheOverview::class)
            ->call('toggleDiamond', $fiche->id);

        $this->assertFalse(Cache::has('home:recent-diamond'));
    }

    public function test_quadrant_filter_excludes_unscored_fiches(): void
    {
        $admin = $this->createAdmin();
        $initiative = Initiative::factory()->published()->create();

        // Assessed but failed (null scores)
        Fiche::factory()
            ->for($initiative)->for(User::factory())->published()
            ->create([
                'title' => 'Mislukte beoordeling zxq011',
                'quality_assessed_at' => now(),
                'quality_score' => null,
                'presentation_score' => null,
            ]);

        // Not assessed at all
        Fiche::factory()
            ->for($initiative)->for(User::factory())->published()
            ->create(['title' => 'Onbeoordeeld zxq012']);

        $this->actingAs($admin)
            ->get(route('admin.fiches.index', ['status' => 'q-needswork']))
            ->assertDontSee('Mislukte beoordeling zxq011')
            ->assertDontSee('Onbeoordeeld zxq012');
    }
}
