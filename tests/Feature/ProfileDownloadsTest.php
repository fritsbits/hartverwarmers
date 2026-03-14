<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use App\Models\UserInteraction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileDownloadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_downloads_page_requires_authentication(): void
    {
        $response = $this->get(route('profile.downloads'));
        $response->assertRedirect(route('login'));
    }

    public function test_downloads_page_shows_downloaded_fiches(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
        ]);

        $response = $this->actingAs($user)->get(route('profile.downloads'));
        $response->assertStatus(200);
        $response->assertSee($fiche->title);
    }

    public function test_downloads_page_does_not_show_only_viewed_fiches(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'view',
        ]);

        $response = $this->actingAs($user)->get(route('profile.downloads'));
        $response->assertStatus(200);
        $response->assertDontSee($fiche->title);
    }

    public function test_downloads_page_shows_empty_state(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('profile.downloads'));
        $response->assertStatus(200);
        $response->assertSee('Nog geen downloads');
    }
}
