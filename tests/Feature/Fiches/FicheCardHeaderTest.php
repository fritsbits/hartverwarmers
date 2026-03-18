<?php

namespace Tests\Feature\Fiches;

use App\Models\Fiche;
use App\Models\File;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FicheCardHeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_contributor_show_displays_fiche_list_items_with_previews(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create(['published' => true]);
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id, 'user_id' => $user->id]);

        File::factory()->withPreviews(3)->create(['fiche_id' => $fiche->id]);

        $response = $this->get(route('contributors.show', $user));

        $response->assertStatus(200);
        $response->assertSee('fiche-list-item', escape: false);
    }

    public function test_fiche_card_hides_header_when_no_preview_images(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create(['published' => true]);
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id, 'user_id' => $user->id]);

        File::factory()->create(['fiche_id' => $fiche->id, 'preview_images' => null]);

        $response = $this->get(route('contributors.show', $user));

        $response->assertStatus(200);
        $response->assertDontSee('fiche-card-header');
    }

    public function test_fiche_card_hides_header_when_no_files(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create(['published' => true]);
        Fiche::factory()->published()->create(['initiative_id' => $initiative->id, 'user_id' => $user->id]);

        $response = $this->get(route('contributors.show', $user));

        $response->assertStatus(200);
        $response->assertDontSee('fiche-card-header');
    }

    public function test_card_preview_images_returns_max_three_urls(): void
    {
        $fiche = Fiche::factory()->create();
        File::factory()->withPreviews(5)->create(['fiche_id' => $fiche->id]);
        $fiche->load('files');

        $urls = $fiche->cardPreviewImages(3);

        $this->assertCount(3, $urls);
        $this->assertStringContainsString('/storage/file-previews/', $urls[0]);
    }

    public function test_card_preview_images_returns_empty_for_files_without_previews(): void
    {
        $fiche = Fiche::factory()->create();
        File::factory()->create(['fiche_id' => $fiche->id, 'preview_images' => null]);
        $fiche->load('files');

        $this->assertEmpty($fiche->cardPreviewImages());
    }

    public function test_initiative_page_includes_fiche_data_for_diamond_fiche_with_previews(): void
    {
        $initiative = Initiative::factory()->create(['published' => true]);
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'has_diamond' => true,
        ]);
        File::factory()->withPreviews(2)->create(['fiche_id' => $fiche->id]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertViewHas('ficheAlpineData');
        $ficheData = $response->viewData('ficheAlpineData');
        $this->assertCount(1, $ficheData);
        $this->assertEquals($fiche->id, $ficheData[0]['id']);
    }

    public function test_contributor_show_page_shows_fiche_list_items(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create(['published' => true]);
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'user_id' => $user->id,
        ]);
        File::factory()->withPreviews(2)->create(['fiche_id' => $fiche->id]);

        $response = $this->get(route('contributors.show', $user));

        $response->assertStatus(200);
        $response->assertSee('fiche-list-item', escape: false);
    }

    public function test_diamond_badge_tooltip_does_not_link_to_goals(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->withDiamond()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertDontSee('DIAMANT-filosofie');
        $response->assertDontSee(route('goals.index'));
    }
}
