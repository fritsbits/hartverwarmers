<?php

namespace Tests\Feature\Pages;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_home_page_shows_diamant_section(): void
    {
        Feature::define('diamant-goals', true);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('DIAMANT-kompas');
    }

    public function test_home_page_without_diamant_goals_feature(): void
    {
        Feature::define('diamant-goals', false);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('DIAMANT-kompas');
    }

    public function test_home_page_shows_current_month_featured_fiche(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()
            ->published()
            ->ficheOfMonth(now()->format('Y-m'))
            ->create(['initiative_id' => $initiative->id]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee($fiche->title);
        $response->assertSee('Fiche van de maand');
    }

    public function test_home_page_falls_back_to_most_recent_featured_fiche(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()
            ->published()
            ->ficheOfMonth('2025-06')
            ->create(['initiative_id' => $initiative->id]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee($fiche->title);
        $response->assertSee('Fiche van de maand');
    }

    public function test_home_page_passes_goals_data_to_view(): void
    {
        // Create 3 initiatives for a goal so at least one is eligible
        $goalTag = Tag::factory()->goal()->create(['slug' => 'doel-doen']);
        $initiatives = Initiative::factory()->published()->count(3)->create();
        foreach ($initiatives as $initiative) {
            $initiative->tags()->attach($goalTag->id);
        }

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewHas('goals');
        $response->assertViewHas('defaultGoal');

        $goals = $response->viewData('goals');
        // Only eligible goals (3+ initiatives) are passed
        $this->assertNotEmpty($goals);
        $this->assertArrayHasKey('slug', $goals[0]);
        $this->assertArrayHasKey('tagSlug', $goals[0]);
        $this->assertArrayHasKey('letter', $goals[0]);
        $this->assertArrayHasKey('keyword', $goals[0]);
        $this->assertArrayHasKey('inspiratie', $goals[0]);
    }

    public function test_home_page_loads_initiatives_with_goal_tags(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $goalTag = Tag::factory()->goal()->create(['slug' => 'doel-doen']);
        $themeTag = Tag::factory()->theme()->create(['slug' => 'muziek']);
        $initiative->tags()->attach([$goalTag->id, $themeTag->id]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $initiatives = $response->viewData('initiatives');
        $loadedInitiative = $initiatives->firstWhere('id', $initiative->id);
        $this->assertNotNull($loadedInitiative);
        // Only goal tags should be loaded (not theme tags)
        $this->assertTrue($loadedInitiative->tags->contains('slug', 'doel-doen'));
        $this->assertFalse($loadedInitiative->tags->contains('slug', 'muziek'));
    }

    public function test_home_page_default_goal_comes_from_eligible_goals(): void
    {
        // Create 3 initiatives with the same goal tag to make it eligible
        $goalTag = Tag::factory()->goal()->create(['slug' => 'doel-doen']);
        $initiatives = Initiative::factory()->published()->count(3)->create();
        foreach ($initiatives as $initiative) {
            $initiative->tags()->attach($goalTag->id);
        }

        $response = $this->get('/');

        $response->assertStatus(200);
        $defaultGoal = $response->viewData('defaultGoal');
        $this->assertEquals('doel-doen', $defaultGoal);
    }

    public function test_home_page_shows_diamantjes_section_when_three_or_more_exist(): void
    {
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->withDiamond()->count(3)->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Diamantjes');
        $response->assertViewHas('diamonds');
        $this->assertCount(3, $response->viewData('diamonds'));
    }

    public function test_home_page_hides_diamantjes_section_when_fewer_than_three_exist(): void
    {
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->withDiamond()->count(2)->create([
            'initiative_id' => $initiative->id,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewHas('diamonds');
        $this->assertCount(0, $response->viewData('diamonds'));
    }

    public function test_home_page_diamantjes_section_hidden_when_none_exist(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewHas('diamonds');
        $this->assertCount(0, $response->viewData('diamonds'));
    }
}
