<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DownloadsAndBookmarksTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_access_favorieten_page(): void
    {
        $response = $this->get('/favorieten');
        $response->assertOk();
    }

    public function test_authenticated_users_can_access_favorieten_page(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/favorieten');
        $response->assertOk();
    }

    public function test_old_profile_favorieten_redirects_to_new_url(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/profiel/favorieten');
        $response->assertRedirect('/favorieten');
        $response->assertStatus(301);
    }
}
