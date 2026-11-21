<?php

namespace Tests\Feature;

use App\Features\DiamantGoals;
use App\Livewire\FicheIndex;
use App\Livewire\Search;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Tests\TestCase;

class FicheIndexTest extends TestCase
{
    use RefreshDatabase;

    private function fiche(array $attributes = [], ?Initiative $initiative = null): Fiche
    {
        return Fiche::factory()->published()->create($attributes + [
            'initiative_id' => ($initiative ?? Initiative::factory()->published()->create())->id,
        ]);
    }

    private function goal(string $facet): Tag
    {
        return Tag::factory()->goal()->create(['name' => ucfirst($facet), 'slug' => 'doel-'.$facet]);
    }

    public function test_page_renders_publicly_with_head_and_count(): void
    {
        $this->fiche(['title' => 'Bingo in de tuin']);

        $this->get('/activiteiten')
            ->assertOk()
            ->assertSee('Alle activiteiten')
            ->assertSee('Ontdek')
            ->assertSee('1 activiteiten')
            ->assertSee('Bingo in de tuin')
            ->assertSee('Bekijk de initiatieven');
    }

    public function test_hidden_fiches_are_not_listed(): void
    {
        $this->fiche(['title' => 'Zichtbare fiche']);
        Fiche::factory()->create(['title' => 'Concept fiche', 'initiative_id' => Initiative::factory()->published()->create()->id]);
        $this->fiche(['title' => 'Fiche van offline initiatief'], Initiative::factory()->create());

        Livewire::test(FicheIndex::class)
            ->assertSee('Zichtbare fiche')
            ->assertDontSee('Concept fiche')
            ->assertDontSee('Fiche van offline initiatief')
            ->assertSet('resultCount', 1);
    }

    public function test_search_requires_two_characters_and_matches_title_and_description(): void
    {
        $this->fiche(['title' => 'Quiz over vroeger', 'description' => 'Oude liedjes']);
        $this->fiche(['title' => 'Wandeling', 'description' => 'Een quiz onderweg']);
        $this->fiche(['title' => 'Schilderen', 'description' => 'Verf']);

        Livewire::test(FicheIndex::class)
            ->set('q', 'q')
            ->assertSee('Schilderen')
            ->set('q', 'quiz')
            ->assertSee('Quiz over vroeger')
            ->assertSee('Wandeling')
            ->assertDontSee('Schilderen')
            ->assertSeeHtml('</b> activiteiten gevonden');
    }

    public function test_initiative_filter_is_or_within_and_counts_ignore_own_group(): void
    {
        $a = Initiative::factory()->published()->create(['title' => 'Quiz', 'slug' => 'quiz']);
        $b = Initiative::factory()->published()->create(['title' => 'Bingo', 'slug' => 'bingo']);
        $c = Initiative::factory()->published()->create(['title' => 'Muziek', 'slug' => 'muziek']);
        $this->fiche(['title' => 'A1'], $a);
        $this->fiche(['title' => 'B1'], $b);
        $this->fiche(['title' => 'C1'], $c);

        $component = Livewire::test(FicheIndex::class)
            ->set('initiatief', ['quiz', 'bingo'])
            ->assertSee('A1')
            ->assertSee('B1')
            ->assertDontSee('C1')
            ->assertSeeHtml('</b> activiteiten gevonden');

        $counts = collect($component->get('initiativeOptions'))->pluck('count', 'slug');
        $this->assertSame(1, $counts['muziek']);
        $this->assertSame(1, $counts['quiz']);
    }

    public function test_diamond_filter(): void
    {
        $this->fiche(['title' => 'Gewone fiche']);
        $this->fiche(['title' => 'Diamantfiche', 'has_diamond' => true]);

        Livewire::test(FicheIndex::class)
            ->set('diamantjes', true)
            ->assertSee('Diamantfiche')
            ->assertDontSee('Gewone fiche')
            ->assertSeeHtml('</b> activiteit gevonden');
    }

    public function test_goal_filter_and_cross_filter_counts(): void
    {
        Feature::define(DiamantGoals::class, true);
        $doen = $this->goal('doen');
        $talent = $this->goal('talent');
        $quiz = Initiative::factory()->published()->create(['slug' => 'quiz']);
        $bingo = Initiative::factory()->published()->create(['slug' => 'bingo']);

        $this->fiche(['title' => 'Quiz doen'], $quiz)->tags()->attach($doen);
        $this->fiche(['title' => 'Bingo doen'], $bingo)->tags()->attach($doen);
        $this->fiche(['title' => 'Bingo talent'], $bingo)->tags()->attach($talent);

        $component = Livewire::test(FicheIndex::class)
            ->set('doel', ['doen'])
            ->assertSee('Quiz doen')
            ->assertSee('Bingo doen')
            ->assertDontSee('Bingo talent')
            ->set('initiatief', ['bingo'])
            ->assertSee('Bingo doen')
            ->assertDontSee('Quiz doen')
            ->assertSeeHtml('</b> activiteit gevonden');

        $goalCounts = collect($component->get('goalOptions'))->pluck('count', 'slug');
        $this->assertSame(1, $goalCounts['doen']);
        $this->assertSame(1, $goalCounts['talent']);
        $this->assertSame(0, $goalCounts['inclusief']);
    }

    public function test_goal_filter_is_hidden_and_ignored_when_feature_is_off(): void
    {
        Feature::define(DiamantGoals::class, false);
        $doen = $this->goal('doen');
        $this->fiche(['title' => 'Zonder doel']);
        $this->fiche(['title' => 'Met doel'])->tags()->attach($doen);

        Livewire::test(FicheIndex::class)
            ->assertDontSee('Mensgericht')
            ->set('doel', ['doen'])
            ->assertSee('Zonder doel')
            ->assertSee('Met doel');
    }

    public function test_goal_filter_shows_in_diamant_order_when_feature_is_on(): void
    {
        Feature::define(DiamantGoals::class, true);

        $this->get('/activiteiten')->assertSeeInOrder(['Doen', 'Inclusief', 'Autonomie', 'Mensgericht', 'Anderen', 'Normalisatie', 'Talent']);
    }

    public function test_sort_nieuwste_and_bedankt(): void
    {
        $this->fiche(['title' => 'Oud en geliefd', 'kudos_count' => 9, 'created_at' => now()->subDays(10)]);
        $this->fiche(['title' => 'Nieuw', 'kudos_count' => 0, 'created_at' => now()]);
        $this->fiche(['title' => 'Middel', 'kudos_count' => 9, 'created_at' => now()->subDays(5)]);

        Livewire::test(FicheIndex::class)
            ->assertSeeInOrder(['Nieuw', 'Middel', 'Oud en geliefd'])
            ->set('sort', 'bedankt')
            ->assertSeeInOrder(['Middel', 'Oud en geliefd', 'Nieuw']);
    }

    public function test_sort_verras_is_stable_while_paging_and_complete(): void
    {
        foreach (range(1, 30) as $i) {
            $this->fiche(['title' => "Fiche nummer {$i}", 'created_at' => now()->subMinutes($i)]);
        }

        $component = Livewire::test(FicheIndex::class)->set('sort', 'verras');
        $first = $component->get('fiches')->pluck('id');
        $this->assertCount(24, $first);

        $component->call('loadMore');
        $all = $component->get('fiches')->pluck('id');
        $this->assertCount(30, $all);
        $this->assertSame($first->all(), $all->take(24)->all());
        $this->assertNotSame($all->sort()->values()->all(), $all->all());
    }

    public function test_month_headings_only_for_nieuwste_above_threshold(): void
    {
        foreach (range(1, 25) as $i) {
            $this->fiche(['title' => "Activiteit {$i}", 'created_at' => '2026-08-15 10:00:00']);
        }
        $this->fiche(['title' => 'Oudere activiteit', 'created_at' => '2026-07-02 10:00:00']);

        Livewire::test(FicheIndex::class)
            ->assertSee('Augustus 2026')
            ->assertSee('25 activiteiten')
            ->assertSee('15 aug')
            ->assertDontSee('15 aug 2026')
            ->set('sort', 'bedankt')
            ->assertDontSee('Augustus 2026')
            ->assertSee('15 aug 2026');
    }

    public function test_month_count_is_full_count_not_loaded_count(): void
    {
        foreach (range(1, 30) as $i) {
            $this->fiche(['created_at' => '2026-08-15 10:00:00']);
        }

        Livewire::test(FicheIndex::class)
            ->assertSee('30 activiteiten')
            ->assertSee('24 van 30 getoond');
    }

    public function test_flat_grid_with_full_dates_at_or_below_threshold(): void
    {
        foreach (range(1, 24) as $i) {
            $this->fiche(['created_at' => '2026-08-28 10:00:00']);
        }

        Livewire::test(FicheIndex::class)
            ->assertDontSee('Augustus 2026')
            ->assertSee('28 aug 2026')
            ->assertDontSee('Toon meer activiteiten');
    }

    public function test_load_more_reveals_next_page_and_reset_on_filter_change(): void
    {
        foreach (range(1, 50) as $i) {
            $this->fiche(['created_at' => now()->subMinutes($i)]);
        }

        Livewire::test(FicheIndex::class)
            ->assertSee('Toon meer activiteiten')
            ->assertSee('24 van 50 getoond')
            ->call('loadMore')
            ->assertSee('48 van 50 getoond')
            ->set('diamantjes', true)
            ->assertSet('limit', 24);
    }

    public function test_empty_state_and_clear_filters(): void
    {
        $this->fiche(['title' => 'Bestaand']);

        Livewire::test(FicheIndex::class)
            ->set('q', 'zzzzzz')
            ->assertSee('Geen activiteiten gevonden')
            ->assertSee('Wis alle filters')
            ->call('clearFilters')
            ->assertSet('q', '')
            ->assertSee('Bestaand');
    }

    public function test_active_filters_show_as_removable_pills(): void
    {
        $quiz = Initiative::factory()->published()->create(['title' => 'Quiz', 'slug' => 'quiz']);
        $this->fiche([], $quiz);

        Livewire::test(FicheIndex::class)
            ->set('initiatief', ['quiz'])
            ->assertSee('Verwijder filter Quiz')
            ->call('removeFilter', 'initiatief', 'quiz')
            ->assertSet('initiatief', [])
            ->assertDontSee('Verwijder filter Quiz');
    }

    public function test_filter_state_is_bound_to_the_url(): void
    {
        $this->fiche(['title' => 'Quiz vandaag']);
        $this->fiche(['title' => 'Schilderen']);

        $this->get('/activiteiten?q=quiz&sort=bedankt&diamantjes=0')
            ->assertSee('Quiz vandaag')
            ->assertDontSee('Schilderen');

        Livewire::withQueryParams(['q' => 'quiz', 'initiatief' => ['x'], 'sort' => 'bedankt'])
            ->test(FicheIndex::class)
            ->assertSet('q', 'quiz')
            ->assertSet('sort', 'bedankt')
            ->assertSet('initiatief', ['x']);
    }

    public function test_search_dropdown_links_to_all_results(): void
    {
        $this->fiche(['title' => 'Quiz vandaag']);

        Livewire::test(Search::class)
            ->set('query', 'quiz')
            ->assertSee('Toon alle resultaten')
            ->assertSeeHtml('/activiteiten?q=quiz');

        Livewire::test(Search::class)
            ->set('query', 'nietsgevonden')
            ->assertDontSee('Toon alle resultaten');
    }

    public function test_entry_points_link_to_the_index(): void
    {
        $this->fiche();

        foreach (['/', '/initiatieven'] as $path) {
            $this->get($path)->assertSee(route('fiches.index'), false);
        }
        $this->get('/sitemap.xml')->assertSee(route('fiches.index'), false);
    }
}
