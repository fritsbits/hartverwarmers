<?php

namespace Tests\Feature\Fiches;

use App\Models\Fiche;
use App\Models\File;
use App\Models\Initiative;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FicheShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_fiche_show_displays_author_info(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Jan',
            'last_name' => 'Peeters',
            'function_title' => 'Activiteitencoördinator',
            'organisation' => 'WZC De Linde',
        ]);
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'user_id' => $user->id,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('Jan Peeters');
        $response->assertSee('WZC De Linde');
    }

    public function test_fiche_show_displays_other_fiches_from_same_initiative(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);
        $other = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('Meer fiches');
        $response->assertSee($other->title);
    }

    public function test_fiche_show_displays_duration_and_group_size_in_hero(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'materials' => [
                'duration' => '45 minuten',
                'group_size' => '6-8 personen',
            ],
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('45 minuten');
        $response->assertSee('6-8 personen');
    }

    public function test_fiche_show_displays_files(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);
        File::factory()->create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'activiteit-plan.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('Download bestand');
        $response->assertSee(route('fiches.download', [$initiative, $fiche]));
    }

    public function test_fiche_show_does_not_display_related_initiatives(): void
    {
        $tag = Tag::factory()->theme()->create();
        $initiative = Initiative::factory()->published()->create();
        $initiative->tags()->attach($tag);
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);
        $fiche->tags()->attach($tag);

        $related = Initiative::factory()->published()->create();
        $related->tags()->attach($tag);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertDontSee('Gerelateerde initiatieven');
    }

    public function test_fiche_show_displays_back_link_to_initiative(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee($initiative->title);
        $response->assertSee(route('initiatives.show', $initiative));
    }

    public function test_fiche_show_displays_description(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'description' => '<p>Een gedetailleerde uitleg van deze activiteit.</p>',
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('Een gedetailleerde uitleg van deze activiteit.');
    }

    public function test_fiche_show_displays_practical_tips_fallback(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'practical_tips' => '<p>Begin altijd met een korte inleiding.</p>',
            'materials' => null,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('Praktische informatie');
        $response->assertSee('Praktische tips');
        $response->assertSee('Begin altijd met een korte inleiding.');
    }

    public function test_fiche_show_displays_structured_materials_sections(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'materials' => [
                'preparation' => '<p>Maak alles klaar op tafel.</p>',
                'inventory' => '<p>Papier, schaar, lijm.</p>',
                'process' => '<p>Stap voor stap uitleg.</p>',
            ],
            'practical_tips' => null,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('Praktische informatie');
        $response->assertSee('Voorbereiding');
        $response->assertSee('Benodigdheden');
        $response->assertSee('Werkwijze');
        $response->assertSee('Maak alles klaar op tafel.');
        $response->assertSee('Papier, schaar, lijm.');
        $response->assertSee('Stap voor stap uitleg.');
    }

    public function test_fiche_show_hides_practical_section_when_empty(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'materials' => null,
            'practical_tips' => null,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertDontSee('Praktische informatie');
    }

    public function test_fiche_show_displays_practical_sections_without_materials_meta(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'materials' => [
                'preparation' => '<p>Leg alles klaar.</p>',
            ],
            'practical_tips' => null,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('Praktische informatie');
        $response->assertSee('Voorbereiding');
        $response->assertDontSee('Materiaal');
    }

    public function test_fiche_show_displays_carousel_when_files_have_previews(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);
        File::factory()->pptx()->withPreviews(2)->count(2)->create([
            'fiche_id' => $fiche->id,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('data-carousel', false);
    }

    public function test_fiche_show_hides_carousel_when_no_files(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertDontSee('data-carousel', false);
    }

    public function test_fiche_show_displays_meta_in_hero(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'materials' => ['duration' => '45 min', 'group_size' => '6-12'],
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('45 min');
        $response->assertSee('6-12 pers.');
    }

    public function test_fiche_show_displays_preview_images_in_carousel(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);
        File::factory()->pptx()->withPreviews(3)->create([
            'fiche_id' => $fiche->id,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('data-carousel', false);
        $response->assertSee('data-preview-image', false);
    }

    public function test_fiche_show_hides_carousel_for_files_without_previews(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);
        File::factory()->create([
            'fiche_id' => $fiche->id,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertDontSee('data-carousel', false);
        $response->assertSee('Download bestand');
    }

    public function test_fiche_show_displays_preview_indicator_when_file_has_more_slides(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);
        File::factory()->pptx()->withPreviews(3)->create([
            'fiche_id' => $fiche->id,
            'total_slides' => 10,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('data-preview-counter', false);
        $response->assertSee('+7', false);
    }

    public function test_fiche_show_hides_preview_indicator_when_all_slides_shown(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);
        File::factory()->pptx()->withPreviews(3)->create([
            'fiche_id' => $fiche->id,
            'total_slides' => 3,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertDontSee('data-preview-counter', false);
    }

    public function test_fiche_show_displays_toegevoegd_door(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Anna',
            'last_name' => 'Janssens',
        ]);
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'user_id' => $user->id,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('Anna Janssens');
    }

    public function test_author_sees_suggestion_nudge_when_low_score(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()
            ->withSuggestions()
            ->withPresentationScore(40)
            ->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);

        $this->actingAs($user)
            ->get(route('fiches.show', [$initiative, $fiche]))
            ->assertSee('Zet je fiche nét wat scherper');
    }

    public function test_author_does_not_see_nudge_when_suggestions_applied(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()
            ->withSuggestions(['applied' => ['description', 'preparation']])
            ->withPresentationScore(40)
            ->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);

        $this->actingAs($user)
            ->get(route('fiches.show', [$initiative, $fiche]))
            ->assertDontSee('Zet je fiche nét wat scherper');
    }

    public function test_author_does_not_see_nudge_when_high_score(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()
            ->withSuggestions()
            ->withPresentationScore(80)
            ->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);

        $this->actingAs($user)
            ->get(route('fiches.show', [$initiative, $fiche]))
            ->assertDontSee('Maak het makkelijker voor collega');
    }

    public function test_non_author_does_not_see_nudge(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()
            ->withSuggestions()
            ->withPresentationScore(40)
            ->create(['user_id' => $author->id, 'initiative_id' => $initiative->id]);

        $this->actingAs($viewer)
            ->get(route('fiches.show', [$initiative, $fiche]))
            ->assertDontSee('Maak het makkelijker voor collega');
    }

    public function test_fiche_show_has_print_button(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('Afdrukken');
        $response->assertSee('window.print()', false);
    }

    public function test_fiche_show_has_print_view_with_title(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'title' => 'Muziek en beweging',
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('id="print-view"', false);
        $response->assertSeeText('Muziek en beweging');
    }

    public function test_fiche_show_print_view_contains_practical_sections(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'materials' => [
                'preparation' => '<p>Zet muziek klaar</p>',
                'inventory' => '<p>Bluetooth-speaker</p>',
                'process' => '<p>Begin rustig</p>',
            ],
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('Zet muziek klaar', false);
        $response->assertSee('Bluetooth-speaker', false);
        $response->assertSee('Begin rustig', false);
    }

    public function test_fiche_show_print_view_contains_cta_and_url(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('Heb je deze activiteit uitgevoerd', false);
        $response->assertSee(route('fiches.show', [$initiative, $fiche]), false);
    }

    public function test_fiche_show_displays_kudos_when_no_files(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('Geef een hartje');
        $response->assertSee('Bewaar als favoriet');
    }

    public function test_fiche_show_displays_icon_card_when_no_files(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'icon' => 'music',
        ]);

        $response = $this->get(route('fiches.show', [$initiative, $fiche]));

        $response->assertStatus(200);
        $response->assertSee('py-10', false);
    }
}
