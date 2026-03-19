<?php

namespace Tests\Feature\Fiches;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiamantjesCuratorSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggle_diamond_redirects_to_custom_url_when_redirect_param_given(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'has_diamond' => false,
        ]);

        $response = $this->actingAs($admin)->post(
            route('fiches.toggleDiamond', [$initiative, $fiche]),
            ['_redirect' => route('diamantjes.index')]
        );

        $response->assertRedirect(route('diamantjes.index'));
    }

    public function test_toggle_diamond_redirects_to_fiche_show_by_default(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'has_diamond' => false,
        ]);

        $response = $this->actingAs($admin)->post(
            route('fiches.toggleDiamond', [$initiative, $fiche])
        );

        $response->assertRedirect(route('fiches.show', [$initiative, $fiche]));
    }

    public function test_admin_sees_curator_suggestions_panel(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->count(3)->create([
            'initiative_id' => $initiative->id,
            'has_diamond' => false,
            'quality_score' => 75,
        ]);

        $response = $this->actingAs($admin)->get(route('diamantjes.index'));

        $response->assertStatus(200);
        $response->assertSee('Kandidaten voor een diamantje');
    }

    public function test_curator_sees_curator_suggestions_panel(): void
    {
        $curator = User::factory()->curator()->create();
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->count(3)->create([
            'initiative_id' => $initiative->id,
            'has_diamond' => false,
            'quality_score' => 75,
        ]);

        $response = $this->actingAs($curator)->get(route('diamantjes.index'));

        $response->assertStatus(200);
        $response->assertSee('Kandidaten voor een diamantje');
    }

    public function test_contributor_does_not_see_curator_suggestions_panel(): void
    {
        $contributor = User::factory()->contributor()->create();
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->count(3)->create([
            'initiative_id' => $initiative->id,
            'has_diamond' => false,
            'quality_score' => 75,
        ]);

        $response = $this->actingAs($contributor)->get(route('diamantjes.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Kandidaten voor een diamantje');
    }

    public function test_guest_does_not_see_curator_suggestions_panel(): void
    {
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->count(3)->create([
            'initiative_id' => $initiative->id,
            'has_diamond' => false,
            'quality_score' => 75,
        ]);

        $response = $this->get(route('diamantjes.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Kandidaten voor een diamantje');
    }

    public function test_diamond_fiches_are_excluded_from_suggestions(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'has_diamond' => true,
            'quality_score' => 90,
        ]);

        $response = $this->actingAs($admin)->get(route('diamantjes.index'));

        // The panel should not appear — all qualifying fiches already have a diamond
        $response->assertDontSee('Kandidaten voor een diamantje');
    }

    public function test_low_score_fiches_are_excluded_from_suggestions(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'has_diamond' => false,
            'quality_score' => 50,
            'title' => 'Te lage score',
        ]);

        $response = $this->actingAs($admin)->get(route('diamantjes.index'));

        $response->assertDontSee('Te lage score');
    }

    public function test_null_score_fiches_are_excluded_from_suggestions(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'has_diamond' => false,
            'quality_score' => null,
            'title' => 'Geen score',
        ]);

        $response = $this->actingAs($admin)->get(route('diamantjes.index'));

        $response->assertDontSee('Geen score');
    }

    public function test_unpublished_fiches_are_excluded_from_suggestions(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->create([
            'initiative_id' => $initiative->id,
            'published' => false,
            'has_diamond' => false,
            'quality_score' => 80,
            'title' => 'Niet gepubliceerd',
        ]);

        $response = $this->actingAs($admin)->get(route('diamantjes.index'));

        $response->assertDontSee('Niet gepubliceerd');
    }
}
