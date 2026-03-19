<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackLastVisitTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_last_visited_at_on_first_visit(): void
    {
        $user = User::factory()->create(['last_visited_at' => null]);

        $this->actingAs($user)->get('/');

        $user->refresh();
        $this->assertNotNull($user->last_visited_at);
        $this->assertTrue($user->last_visited_at->isToday());
    }

    public function test_does_not_update_last_visited_at_within_one_hour(): void
    {
        $oneHourAgo = now()->subMinutes(30);
        $user = User::factory()->create(['last_visited_at' => $oneHourAgo]);

        $this->actingAs($user)->get('/');

        $user->refresh();
        $this->assertTrue($user->last_visited_at->diffInSeconds($oneHourAgo) < 2);
    }

    public function test_updates_last_visited_at_after_one_hour(): void
    {
        $twoHoursAgo = now()->subHours(2);
        $user = User::factory()->create(['last_visited_at' => $twoHoursAgo]);

        $this->actingAs($user)->get('/');

        $user->refresh();
        $this->assertTrue($user->last_visited_at->gt($twoHoursAgo));
    }

    public function test_does_not_crash_for_guests(): void
    {
        $response = $this->get('/');

        $response->assertSuccessful();
    }
}
