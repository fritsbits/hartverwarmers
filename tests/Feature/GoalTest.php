<?php

namespace Tests\Feature;

use App\Models\Initiative;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_goals_index_displays_all_facets(): void
    {
        $response = $this->get(route('goals.index'));

        $response->assertStatus(200);
        $response->assertSee('Doen');
        $response->assertSee('Inclusief');
        $response->assertSee('Autonomie');
        $response->assertSee('Mensgericht');
        $response->assertSee('Anderen');
        $response->assertSee('Normalisatie');
        $response->assertSee('Talent');
    }

    public function test_goals_index_shows_initiative_counts(): void
    {
        $goalTag = Tag::factory()->goal()->create(['name' => 'Doen', 'slug' => 'doel-doen']);
        $initiative = Initiative::factory()->published()->create();
        $initiative->tags()->attach($goalTag);

        $response = $this->get(route('goals.index'));

        $response->assertStatus(200);
        $response->assertSee('1 initiatief');
    }

    public function test_goals_show_displays_facet_details(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertSee('Talent');
        $response->assertSee('Focussen op wat iemand');
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

    public function test_goals_show_displays_author_attribution(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertSee('Maite Mallentjer');
        $response->assertSee('pedagoog dagbesteding');
    }

    public function test_goals_show_displays_practice_section(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertSee('In de praktijk');
        $response->assertSee('Herkenbare momenten uit het dagelijks leven.');
    }

    public function test_goals_show_displays_reflection_section(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertSee('Vragen voor jezelf');
        $response->assertSee('Niet als test, maar als spiegel.');
    }

    public function test_goals_show_displays_tip_box(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertSee('Tip: verrijk een bestaand initiatief');
    }

    public function test_goals_show_displays_other_facets_grid(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertSee('Ontdek de andere doelstellingen');
        $response->assertSee('Doen');
        $response->assertSee('Inclusief');
        $response->assertSee('Autonomie');
        $response->assertSee('Mensgericht');
        $response->assertSee('Anderen');
        $response->assertSee('Normalisatie');
    }

    public function test_goals_show_displays_total_initiative_count(): void
    {
        Initiative::factory()->published()->count(5)->create();

        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertSee('Alle 5 initiatieven bekijken');
    }

    public function test_goals_show_displays_quote_card(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertSee('Niet: wat kan deze bewoner nog? Maar: wat kan deze persoon ons brengen?');
    }

    public function test_goals_show_displays_practice_example_names_and_roles(): void
    {
        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertSee('Frans');
        $response->assertSee('Boekhouder');
        $response->assertSee('Wiske');
        $response->assertSee('Zangeres');
        $response->assertSee('Meneer Peeters');
        $response->assertSee('Tuinier');
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

    public function test_goals_show_displays_facet_initiative_count(): void
    {
        $goalTag = Tag::factory()->goal()->create(['name' => 'Talent', 'slug' => 'doel-talent']);
        $initiatives = Initiative::factory()->published()->count(4)->create();
        $initiatives->each(fn ($i) => $i->tags()->attach($goalTag));

        $response = $this->get(route('goals.show', 'talent'));

        $response->assertStatus(200);
        $response->assertSee('Alle 4 initiatieven voor Talent bekijken');
    }
}
