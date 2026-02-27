<?php

namespace Tests\Feature;

use App\Livewire\Search;
use App\Models\Fiche;
use App\Models\Initiative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_initiatives_matching_title(): void
    {
        $matching = Initiative::factory()->published()->create(['title' => 'Muziektherapie voor bewoners']);
        Initiative::factory()->published()->create(['title' => 'Wandelen in het park']);

        Livewire::test(Search::class)
            ->set('query', 'Muziek')
            ->assertSee('Muziektherapie voor bewoners')
            ->assertDontSee('Wandelen in het park');
    }

    public function test_search_returns_initiatives_matching_description(): void
    {
        $matching = Initiative::factory()->published()->create([
            'title' => 'Activiteit A',
            'description' => 'Een knutselmiddag voor bewoners',
        ]);
        Initiative::factory()->published()->create([
            'title' => 'Activiteit B',
            'description' => 'Wandelen door de tuin',
        ]);

        Livewire::test(Search::class)
            ->set('query', 'knutsel')
            ->assertSee('Activiteit A')
            ->assertDontSee('Activiteit B');
    }

    public function test_search_returns_fiches_matching_title(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $matching = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'title' => 'Kookworkshop voor senioren',
        ]);
        Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'title' => 'Bingo avond',
        ]);

        Livewire::test(Search::class)
            ->set('query', 'Kookworkshop')
            ->assertSee('Kookworkshop voor senioren')
            ->assertDontSee('Bingo avond');
    }

    public function test_search_returns_fiches_matching_description(): void
    {
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'title' => 'Fiche A',
            'description' => 'Samen koken met verse groenten',
        ]);
        Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'title' => 'Fiche B',
            'description' => 'Muziek luisteren',
        ]);

        Livewire::test(Search::class)
            ->set('query', 'koken')
            ->assertSee('Fiche A')
            ->assertDontSee('Fiche B');
    }

    public function test_search_only_returns_published_initiatives(): void
    {
        Initiative::factory()->published()->create(['title' => 'Gepubliceerd initiatief']);
        Initiative::factory()->create(['title' => 'Ongepubliceerd initiatief', 'published' => false]);

        Livewire::test(Search::class)
            ->set('query', 'initiatief')
            ->assertSee('Gepubliceerd initiatief')
            ->assertDontSee('Ongepubliceerd initiatief');
    }

    public function test_search_only_returns_published_fiches(): void
    {
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'title' => 'Gepubliceerde fiche',
        ]);
        Fiche::factory()->create([
            'initiative_id' => $initiative->id,
            'title' => 'Ongepubliceerde fiche',
            'published' => false,
        ]);

        Livewire::test(Search::class)
            ->set('query', 'fiche')
            ->assertSee('Gepubliceerde fiche')
            ->assertDontSee('Ongepubliceerde fiche');
    }

    public function test_search_requires_minimum_two_characters(): void
    {
        Initiative::factory()->published()->create(['title' => 'Muziektherapie']);

        Livewire::test(Search::class)
            ->set('query', 'M')
            ->assertDontSee('Muziektherapie')
            ->assertSee('Typ minstens 2 tekens');
    }

    public function test_empty_search_shows_prompt(): void
    {
        Livewire::test(Search::class)
            ->assertSee('Typ minstens 2 tekens');
    }

    public function test_search_shows_no_results_message(): void
    {
        Livewire::test(Search::class)
            ->set('query', 'xyznonexistent')
            ->assertSee('Geen resultaten gevonden');
    }

    public function test_search_returns_both_initiatives_and_fiches(): void
    {
        $initiative = Initiative::factory()->published()->create(['title' => 'Kookworkshop']);
        Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'title' => 'Uitwerking: Kookworkshop - versie 1',
        ]);

        Livewire::test(Search::class)
            ->set('query', 'Kookworkshop')
            ->assertSee('Kookworkshop')
            ->assertSee('Uitwerking: Kookworkshop - versie 1')
            ->assertSee('Initiatieven')
            ->assertSee('Fiches');
    }
}
