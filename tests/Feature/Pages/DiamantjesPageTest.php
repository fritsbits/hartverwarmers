<?php

namespace Tests\Feature\Pages;

use App\Models\Fiche;
use App\Models\Initiative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DiamantjesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_diamantjes_page_loads_for_guests(): void
    {
        $response = $this->get('/diamantjes');

        $response->assertStatus(200);
    }

    public function test_diamantjes_page_shows_all_diamond_fiches(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $diamond = Fiche::factory()->published()->withDiamond()->create([
            'initiative_id' => $initiative->id,
        ]);
        $regular = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'has_diamond' => false,
        ]);

        $response = $this->get('/diamantjes');

        $response->assertStatus(200);
        $response->assertSee($diamond->title);
        $response->assertDontSee($regular->title);
    }

    public function test_diamantjes_page_shows_count(): void
    {
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->withDiamond()->count(3)->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->get('/diamantjes');

        $response->assertStatus(200);
        $response->assertSee('3 fiches');
    }

    public function test_diamantjes_page_passes_fiches_to_view(): void
    {
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->withDiamond()->count(2)->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->get('/diamantjes');

        $response->assertStatus(200);
        $response->assertViewHas('fiches');
        $this->assertCount(2, $response->viewData('fiches'));
    }

    public function test_diamantjes_page_excludes_unpublished_fiches(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $unpublished = Fiche::factory()->withDiamond()->create([
            'initiative_id' => $initiative->id,
            'published' => false,
        ]);

        $response = $this->get('/diamantjes');

        $response->assertStatus(200);
        $response->assertDontSee($unpublished->title);
    }

    public function test_diamantjes_page_shows_singular_count(): void
    {
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->withDiamond()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->get('/diamantjes');

        $response->assertStatus(200);
        $response->assertSee('1 fiche');
    }

    public function test_diamantjes_link_appears_in_footer(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee(route('diamantjes.index'));
    }

    public function test_first_fiche_is_shown_as_featured(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $first = Fiche::factory()->published()->withDiamond()->create([
            'initiative_id' => $initiative->id,
            'title' => 'Featured Fiche Title',
        ]);
        Fiche::factory()->published()->withDiamond()->create([
            'initiative_id' => $initiative->id,
            'title' => 'Second Fiche Title',
        ]);

        $response = $this->get('/diamantjes');

        $response->assertStatus(200);
        $response->assertSee('Featured Fiche Title');
        // Featured card uses a data attribute to distinguish it from the grid cards
        $response->assertSee('data-featured-card', false);
    }

    public function test_sidebar_shows_hoe_kiezen_we_box(): void
    {
        $response = $this->get('/diamantjes');

        $response->assertStatus(200);
        $response->assertSee('Hoe kiezen we?');
        $response->assertSeeText('diamantjes zijn');
    }

    public function test_empty_state_still_shows_sidebar(): void
    {
        $response = $this->get('/diamantjes');

        $response->assertStatus(200);
        $response->assertSee('Hoe kiezen we?');
        $response->assertSee('Er zijn nog geen diamantjes');
    }

    public function test_diamantjes_ordered_by_award_time_not_fiche_creation(): void
    {
        Carbon::setTestNow('2026-05-13 12:00:00');

        $initiative = Initiative::factory()->published()->create();

        $oldFicheRecentAward = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'title' => 'Old fiche, recently awarded',
            'has_diamond' => true,
            'created_at' => now()->subYears(3),
            'diamond_awarded_at' => now()->subMinutes(5),
        ]);

        Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'title' => 'Newer fiche, older award',
            'has_diamond' => true,
            'created_at' => now()->subMonths(2),
            'diamond_awarded_at' => now()->subMonths(2),
        ]);

        $response = $this->get('/diamantjes');

        $response->assertStatus(200);
        $this->assertSame($oldFicheRecentAward->id, $response->viewData('fiches')->first()->id);
    }
}
