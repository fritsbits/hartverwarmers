<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FicheVanDeMaandTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_fiche_of_month(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $response = $this->actingAs($admin)->post(
            route('fiches.setFicheOfMonth', [$initiative, $fiche]),
            ['month' => '2026-03']
        );

        $response->assertRedirect(route('fiches.show', [$initiative, $fiche]));
        $this->assertDatabaseHas('fiches', [
            'id' => $fiche->id,
            'featured_month' => '2026-03',
        ]);
    }

    public function test_setting_fiche_of_month_unsets_previous_fiche_for_same_month(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche1 = Fiche::factory()->published()->ficheOfMonth('2026-03')->create(['initiative_id' => $initiative->id]);
        $fiche2 = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $this->actingAs($admin)->post(
            route('fiches.setFicheOfMonth', [$initiative, $fiche2]),
            ['month' => '2026-03']
        );

        $this->assertDatabaseHas('fiches', [
            'id' => $fiche2->id,
            'featured_month' => '2026-03',
        ]);
        $this->assertDatabaseHas('fiches', [
            'id' => $fiche1->id,
            'featured_month' => null,
        ]);
    }

    public function test_admin_can_unset_fiche_of_month(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->ficheOfMonth('2026-03')->create(['initiative_id' => $initiative->id]);

        $response = $this->actingAs($admin)->delete(
            route('fiches.unsetFicheOfMonth', [$initiative, $fiche])
        );

        $response->assertRedirect(route('fiches.show', [$initiative, $fiche]));
        $this->assertDatabaseHas('fiches', [
            'id' => $fiche->id,
            'featured_month' => null,
        ]);
    }

    public function test_non_admin_cannot_set_fiche_of_month(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $response = $this->actingAs($user)->post(
            route('fiches.setFicheOfMonth', [$initiative, $fiche]),
            ['month' => '2026-03']
        );

        $response->assertStatus(403);
    }

    public function test_invalid_month_format_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $response = $this->actingAs($admin)->post(
            route('fiches.setFicheOfMonth', [$initiative, $fiche]),
            ['month' => 'invalid']
        );

        $response->assertSessionHasErrors('month');
    }

    public function test_archive_page_shows_featured_fiches(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->ficheOfMonth('2026-02')->create(['initiative_id' => $initiative->id]);

        $response = $this->get(route('fiches.ficheVanDeMaand'));

        $response->assertStatus(200);
        $response->assertSee($fiche->title);
        $response->assertSee('Fiches van de maand');
    }

    public function test_archive_page_does_not_show_non_featured_fiches(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $response = $this->get(route('fiches.ficheVanDeMaand'));

        $response->assertStatus(200);
        $response->assertDontSee($fiche->title);
    }
}
