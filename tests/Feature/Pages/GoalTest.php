<?php

namespace Tests\Feature\Pages;

use App\Models\Initiative;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Tests\TestCase;

class GoalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Feature::define('diamant-goals', true);
    }

    public function test_goals_index_displays_all_facets(): void
    {
        $response = $this->get(route('goals.index'));

        $response->assertStatus(200);
        $response->assertSee('Zeven doelen om bewoners te laten schitteren');
        $response->assertSee('Doen');
        $response->assertSee('Inclusief');
        $response->assertSee('Autonomie');
        $response->assertSee('Mensgericht');
        $response->assertSee('Anderen');
        $response->assertSee('Normalisatie');
        $response->assertSee('Talent');
    }

    public function test_goals_index_displays_hero_content(): void
    {
        $response = $this->get(route('goals.index'));

        $response->assertStatus(200);
        $response->assertSee('Zeven doelen om bewoners te laten schitteren');
        $response->assertSee('Het DIAMANT-model');
        $response->assertSee('Het DIAMANT-model biedt activiteitenbegeleiders');
        $response->assertSee('Elke doelstelling vertrekt vanuit het perspectief van de bewoner');
    }

    public function test_goals_index_displays_bewoner_subtitles(): void
    {
        $response = $this->get(route('goals.index'));

        $response->assertStatus(200);
        $response->assertSee('Bewoners doen zelf mee');
        $response->assertSee('Bewoners schitteren');
    }

    public function test_goals_index_displays_developers(): void
    {
        $response = $this->get(route('goals.index'));

        $response->assertStatus(200);
        $response->assertSee('Maite Mallentjer');
        $response->assertSee('Nadine Praet');
        $response->assertSee('Pedagoog dagbesteding');
        $response->assertSee('Onderzoeker ouderenzorg');
        $response->assertDontSee('FEBI-congres 2025');
    }

    public function test_goals_index_displays_research_section(): void
    {
        $response = $this->get(route('goals.index'));

        $response->assertStatus(200);
        $response->assertSee('Gebouwd op onderzoek', false);
        $response->assertSee('Betekenisvolle Activiteiten Methode (BAM)');
        $response->assertSee('A Sense of Home');
        $response->assertSee('t Klikt', false);
        $response->assertSee('politeia.be', false);
    }

    public function test_goals_index_does_not_show_old_footer_credit(): void
    {
        $response = $this->get(route('goals.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Het DIAMANT-model is ontwikkeld door Maite Mallentjer.');
    }

    public function test_goals_show_displays_facet_details(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertSee('Talent');
        $response->assertSee('vertrekken vanuit krachten');
    }

    public function test_goals_show_returns_404_for_invalid_slug(): void
    {
        $response = $this->get(route('goals.show', 'ongeldig'));

        $response->assertStatus(404);
    }

    public function test_goals_show_lists_tagged_initiatives(): void
    {
        $goalTag = Tag::factory()->goal()->create(['name' => 'Talent', 'slug' => 'doel-talent']);
        $initiative = Initiative::factory()->published()->create();
        $initiative->tags()->attach($goalTag);

        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertSee($initiative->title);
    }

    public function test_goals_show_only_shows_published_initiatives(): void
    {
        $goalTag = Tag::factory()->goal()->create(['name' => 'Doen', 'slug' => 'doel-doen']);

        $published = Initiative::factory()->published()->create();
        $published->tags()->attach($goalTag);

        $unpublished = Initiative::factory()->create(['published' => false]);
        $unpublished->tags()->attach($goalTag);

        $response = $this->get(route('goals.show', 'doen'));

        $response->assertStatus(200);
        $response->assertSee($published->title);
        $response->assertDontSee($unpublished->title);
    }

    public function test_goals_show_hides_practice_section_when_disabled(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertDontSee('Zo herken je het');
    }

    public function test_goals_show_displays_checklist(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertSee('Checklist');
        $response->assertSee('talenten en vaardigheden');
    }

    public function test_goals_show_displays_other_facets_grid(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertSee('Verwante doelstellingen');
        $response->assertSee('Mensgericht');
        $response->assertSee('Doen');
    }

    public function test_goals_show_displays_section_labels(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertSee('Checklist');
        $response->assertSee('DIAMANT-kompas');
        $response->assertDontSee('Vragen voor jezelf');
    }

    public function test_goals_show_displays_quote_paper_with_core_question(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertSee('Zie je wat je bewoners nog wél kunnen');
    }

    public function test_goals_show_displays_reframe_quote(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertSee('Wat kan deze bewoner nog');
        $response->assertSee('Wat kan deze persoon ons brengen');
    }

    public function test_goals_show_hides_practice_examples_when_disabled(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertDontSee('Hij leest voor met zijn heldere stem');
    }

    public function test_goals_show_limits_initiatives_to_six(): void
    {
        $goalTag = Tag::factory()->goal()->create(['name' => 'Talent', 'slug' => 'doel-talent']);
        $initiatives = Initiative::factory()->published()->count(8)->create();
        $initiatives->each(fn ($i) => $i->tags()->attach($goalTag));

        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);

        $displayed = 0;
        foreach ($initiatives as $initiative) {
            if (str_contains($response->getContent(), e($initiative->title))) {
                $displayed++;
            }
        }

        $this->assertLessThanOrEqual(6, $displayed);
    }

    public function test_goals_show_does_not_display_read_more_buttons(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertDontSee('initiatieven bekijken');
    }

    public function test_goals_show_displays_core_question(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertSee('Zie je wat je bewoners nog');
    }

    public function test_goals_show_does_not_display_contrast_columns(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertDontSee('Zo wel');
        $response->assertDontSee('Zo niet');
    }

    public function test_goals_show_does_not_display_adaptations_block(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertDontSee('Wat als een bewoner zegt');
    }

    public function test_goals_show_displays_related_facets(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertSee('Mensgericht', false);
        $response->assertSee('Doen', false);
        $response->assertSee('/doelen/mensgericht', false);
    }

    public function test_goals_show_hides_practice_examples_limit_when_disabled(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertDontSee('Frans');
    }
}
