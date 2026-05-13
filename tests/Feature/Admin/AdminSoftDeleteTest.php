<?php

namespace Tests\Feature\Admin;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_soft_delete_initiative(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();

        $response = $this->actingAs($admin)->delete(route('initiatives.destroy', $initiative));

        $response->assertRedirect(route('initiatives.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('initiatives', ['id' => $initiative->id]);
    }

    public function test_admin_can_soft_delete_fiche(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('fiches.destroy', [$initiative, $fiche]));

        $response->assertRedirect(route('initiatives.show', $initiative));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('fiches', ['id' => $fiche->id]);
    }

    public function test_non_admin_gets_403_on_initiative_delete(): void
    {
        $contributor = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        $response = $this->actingAs($contributor)->delete(route('initiatives.destroy', $initiative));

        $response->assertStatus(403);
        $this->assertDatabaseHas('initiatives', ['id' => $initiative->id, 'deleted_at' => null]);
    }

    public function test_non_admin_gets_403_on_fiche_delete(): void
    {
        $contributor = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->actingAs($contributor)->delete(route('fiches.destroy', [$initiative, $fiche]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('fiches', ['id' => $fiche->id, 'deleted_at' => null]);
    }

    public function test_guest_gets_redirected_on_initiative_delete(): void
    {
        $initiative = Initiative::factory()->published()->create();

        $response = $this->delete(route('initiatives.destroy', $initiative));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_gets_redirected_on_fiche_delete(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->delete(route('fiches.destroy', [$initiative, $fiche]));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_sees_delete_button_on_initiative_show(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();

        $response = $this->actingAs($admin)->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertSee('Verwijderen');
    }

    public function test_contributor_does_not_see_delete_button_on_initiative_show(): void
    {
        $contributor = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();

        $response = $this->actingAs($contributor)->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertDontSee('Verwijderen');
    }

    public function test_admin_sees_delete_button_on_fiche_show(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->actingAs($admin)->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('Verwijderen');
    }

    public function test_contributor_does_not_see_delete_button_on_fiche_show(): void
    {
        $contributor = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->actingAs($contributor)->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertDontSee('Verwijderen');
    }

    public function test_admin_can_toggle_diamond_on(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'has_diamond' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('fiches.toggleDiamond', [$initiative, $fiche]));

        $response->assertRedirect(route('fiches.show', [$initiative, $fiche]));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('fiches', ['id' => $fiche->id, 'has_diamond' => true]);
        $this->assertNotNull($fiche->fresh()->diamond_awarded_at);
    }

    public function test_admin_can_toggle_diamond_off(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->withDiamond()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->actingAs($admin)->post(route('fiches.toggleDiamond', [$initiative, $fiche]));

        $response->assertRedirect(route('fiches.show', [$initiative, $fiche]));
        $this->assertDatabaseHas('fiches', ['id' => $fiche->id, 'has_diamond' => false]);
        $this->assertNull($fiche->fresh()->diamond_awarded_at);
    }

    public function test_toggling_diamond_invalidates_homepage_cache(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'has_diamond' => false,
        ]);

        Cache::put('home:recent-diamond', 'stale', now()->addMinutes(5));

        $this->actingAs($admin)->post(route('fiches.toggleDiamond', [$initiative, $fiche]));

        $this->assertFalse(Cache::has('home:recent-diamond'));
    }

    public function test_non_admin_cannot_toggle_diamond(): void
    {
        $contributor = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'has_diamond' => false,
        ]);

        $response = $this->actingAs($contributor)->post(route('fiches.toggleDiamond', [$initiative, $fiche]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('fiches', ['id' => $fiche->id, 'has_diamond' => false]);
    }
}
