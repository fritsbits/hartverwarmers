<?php

namespace Tests\Feature;

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

    public function test_home_page_shows_goal_initiative_counts(): void
    {
        Feature::define('diamant-goals', true);

        $goalTag = Tag::factory()->goal()->create(['name' => 'Doen', 'slug' => 'doel-doen']);
        $published = Initiative::factory()->published()->count(3)->create();
        $unpublished = Initiative::factory()->create(['published' => false]);

        foreach ($published as $initiative) {
            $initiative->tags()->attach($goalTag);
        }
        $unpublished->tags()->attach($goalTag);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewHas('goalInitiativeCounts', function (array $counts) {
            return ($counts['doen'] ?? null) === 3;
        });
    }

    public function test_home_page_without_diamant_goals_feature(): void
    {
        Feature::define('diamant-goals', false);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewHas('goalInitiativeCounts', []);
    }
}
