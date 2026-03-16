<?php

namespace Tests\Feature;

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
}
