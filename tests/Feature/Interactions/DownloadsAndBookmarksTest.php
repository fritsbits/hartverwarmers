<?php

namespace Tests\Feature\Interactions;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Like;
use App\Models\User;
use App\Models\UserInteraction;
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

    public function test_shows_downloaded_fiches(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
        ]);

        $response = $this->actingAs($user)->get(route('bookmarks.index'));

        $response->assertOk();
        $response->assertSee($fiche->title);
    }

    public function test_shows_bookmarked_fiches(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);

        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'bookmark',
        ]);

        $response = $this->actingAs($user)->get(route('bookmarks.index'));

        $response->assertOk();
        $response->assertSee($fiche->title);
    }

    public function test_filters_out_orphaned_downloads(): void
    {
        $user = User::factory()->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => 99999,
            'type' => 'download',
        ]);

        $response = $this->actingAs($user)->get(route('bookmarks.index'));

        $response->assertOk();
    }

    public function test_guest_sees_conversion_cta(): void
    {
        $response = $this->get(route('bookmarks.index'));

        $response->assertOk();
        $response->assertSee('Bewaar je favoriete fiches');
        $response->assertSee('Maak een gratis account');
    }

    public function test_shows_empty_states_when_no_items(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('bookmarks.index'));

        $response->assertOk();
        $response->assertSee('Je hebt nog geen fiches gedownload');
        $response->assertSee('Je hebt nog geen fiches als favoriet opgeslagen');
    }
}
