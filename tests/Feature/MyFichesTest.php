<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyFichesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_access_mijn_fiches_page(): void
    {
        $response = $this->get('/mijn-fiches');
        $response->assertOk();
    }

    public function test_authenticated_users_can_access_mijn_fiches_page(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/mijn-fiches');
        $response->assertOk();
    }

    public function test_old_profile_fiches_redirects_to_new_url(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/profiel/fiches');
        $response->assertRedirect('/mijn-fiches');
        $response->assertStatus(301);
    }
}
