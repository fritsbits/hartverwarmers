<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Interest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_loads_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_activities_index_page_loads(): void
    {
        $response = $this->get('/activiteiten');

        $response->assertStatus(200);
    }

    public function test_activities_index_shows_published_activities(): void
    {
        $published = Activity::factory()->create(['published' => true, 'shared' => true]);
        $unpublished = Activity::factory()->create(['published' => false]);

        $response = $this->get('/activiteiten');

        $response->assertStatus(200);
        $response->assertSee($published->title);
        $response->assertDontSee($unpublished->title);
    }

    public function test_activity_detail_page_loads(): void
    {
        $activity = Activity::factory()->create(['published' => true, 'shared' => true]);

        $response = $this->get("/activiteiten/{$activity->slug}");

        $response->assertStatus(200);
        $response->assertSee($activity->title);
    }

    public function test_unpublished_activity_returns_404(): void
    {
        $activity = Activity::factory()->create(['published' => false]);

        $response = $this->get("/activiteiten/{$activity->slug}");

        $response->assertStatus(404);
    }

    public function test_activity_print_page_loads(): void
    {
        $activity = Activity::factory()->create(['published' => true, 'shared' => true]);

        $response = $this->get("/activiteiten/{$activity->slug}/print");

        $response->assertStatus(200);
    }

    public function test_activities_can_be_filtered_by_interest(): void
    {
        $interest = Interest::factory()->create(['type' => 'domain']);
        $activityWithInterest = Activity::factory()->create(['published' => true, 'shared' => true]);
        $activityWithInterest->interests()->attach($interest);

        $activityWithoutInterest = Activity::factory()->create(['published' => true, 'shared' => true]);

        $response = $this->get("/activiteiten?interest={$interest->id}");

        $response->assertStatus(200);
        $response->assertSee($activityWithInterest->title);
        $response->assertDontSee($activityWithoutInterest->title);
    }

    public function test_guest_can_view_activities(): void
    {
        $activity = Activity::factory()->create(['published' => true, 'shared' => true]);

        $response = $this->get('/activiteiten');
        $response->assertStatus(200);

        $response = $this->get("/activiteiten/{$activity->slug}");
        $response->assertStatus(200);
    }
}
