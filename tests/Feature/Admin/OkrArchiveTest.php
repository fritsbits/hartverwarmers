<?php

namespace Tests\Feature\Admin;

use App\Models\Okr\Objective;
use App\Models\User;
use Database\Seeders\OkrSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OkrArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function objective(string $slug = 'presentatiekwaliteit'): Objective
    {
        return Objective::where('slug', $slug)->firstOrFail();
    }

    public function test_admin_can_archive_an_objective(): void
    {
        $this->seed(OkrSeeder::class);
        $objective = $this->objective();

        $response = $this->actingAs($this->admin())
            ->post(route('admin.okrs.archive', $objective));

        $response->assertRedirect(route('admin.dashboard', ['tab' => 'overzicht']));
        $this->assertNotNull($objective->fresh()->archived_at);
    }

    public function test_admin_can_unarchive_an_objective(): void
    {
        $this->seed(OkrSeeder::class);
        $objective = $this->objective();
        $objective->update(['archived_at' => now()]);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.okrs.unarchive', $objective));

        $response->assertRedirect(route('admin.dashboard', ['tab' => $objective->slug]));
        $this->assertNull($objective->fresh()->archived_at);
    }

    public function test_non_admin_cannot_archive_an_objective(): void
    {
        $this->seed(OkrSeeder::class);
        $objective = $this->objective();
        $contributor = User::factory()->create(['role' => 'contributor']);

        $this->actingAs($contributor)
            ->post(route('admin.okrs.archive', $objective))
            ->assertForbidden();

        $this->assertNull($objective->fresh()->archived_at);
    }

    public function test_archived_objective_drops_out_of_tabs_and_stat_cards(): void
    {
        $this->seed(OkrSeeder::class);
        $objective = $this->objective();
        $objective->update(['archived_at' => now()]);

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertNotContains($objective->id, $response->viewData('objectives')->pluck('id')->all());
        $this->assertNotContains($objective->slug, $response->viewData('objectiveStats')->pluck('slug')->all());
    }

    public function test_archived_objective_initiatives_disappear_from_overzicht(): void
    {
        $this->seed(OkrSeeder::class);
        $objective = $this->objective();
        $initiative = $objective->initiatives()->firstOrFail();

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertSee($initiative->label);

        $objective->update(['archived_at' => now()]);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertDontSee($initiative->label);
    }

    public function test_started_initiative_of_archived_objective_disappears_from_overzicht(): void
    {
        $this->seed(OkrSeeder::class);
        $objective = $this->objective();
        $initiative = $objective->initiatives()->firstOrFail();
        $initiative->update(['started_at' => now()->subMonths(2)]);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertSee($initiative->label);

        $objective->update(['archived_at' => now()]);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertDontSee($initiative->label);
    }

    public function test_archived_objective_is_listed_in_the_archived_section(): void
    {
        $this->seed(OkrSeeder::class);
        $objective = $this->objective();
        $objective->initiatives()->firstOrFail()->update(['started_at' => now()->subMonths(2)]);
        $objective->update(['archived_at' => now()]);

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Gearchiveerd');
        $response->assertSee($objective->title);
        $this->assertContains($objective->id, $response->viewData('archivedObjectives')->pluck('id')->all());
    }

    public function test_archived_objective_detail_page_stays_reachable_and_offers_reactivation(): void
    {
        $this->seed(OkrSeeder::class);
        $objective = $this->objective();
        $objective->update(['archived_at' => now()]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.dashboard', ['tab' => $objective->slug]));

        $response->assertOk();
        $response->assertSee($objective->title);
        $response->assertSee('Gearchiveerd');
        $response->assertSee('Heractiveren');
        $response->assertSee(route('admin.okrs.unarchive', $objective));
    }

    public function test_active_objective_detail_page_offers_archiving(): void
    {
        $this->seed(OkrSeeder::class);
        $objective = $this->objective();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.dashboard', ['tab' => $objective->slug]));

        $response->assertOk();
        $response->assertSee('Archiveren');
        $response->assertSee(route('admin.okrs.archive', $objective));
    }

    public function test_detail_page_shows_start_date_derived_from_earliest_started_initiative(): void
    {
        $this->seed(OkrSeeder::class);
        $objective = $this->objective();
        $objective->initiatives()->firstOrFail()->update(['started_at' => '2026-03-17']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.dashboard', ['tab' => $objective->slug]));

        $response->assertOk();
        $response->assertSee('Loopt sinds 17 maart 2026');
    }

    public function test_detail_page_omits_start_date_when_no_initiative_has_started(): void
    {
        $this->seed(OkrSeeder::class);
        $objective = $this->objective();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.dashboard', ['tab' => $objective->slug]));

        $response->assertOk();
        $response->assertDontSee('Loopt sinds');
        $response->assertDontSee('okr-since');
    }

    public function test_archived_detail_page_shows_the_period_it_ran(): void
    {
        $this->seed(OkrSeeder::class);
        $objective = $this->objective();
        $objective->initiatives()->firstOrFail()->update(['started_at' => '2026-03-17']);
        $objective->update(['archived_at' => '2026-08-24 10:00:00']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.dashboard', ['tab' => $objective->slug]));

        $response->assertOk();
        $response->assertSee('Liep van 17 maart 2026 tot 24 augustus 2026');
    }

    public function test_started_at_is_null_when_no_initiative_has_started(): void
    {
        $this->seed(OkrSeeder::class);

        $this->assertNull($this->objective()->startedAt());
    }
}
