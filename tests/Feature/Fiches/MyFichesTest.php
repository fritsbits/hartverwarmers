<?php

namespace Tests\Feature\Fiches;

use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Initiative;
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

    public function test_shows_user_fiches_with_stats(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);

        $response = $this->actingAs($user)->get(route('my-fiches.index'));

        $response->assertOk();
        $response->assertSee($fiche->title);
    }

    public function test_shows_new_comments_alert(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);
        $commenter = User::factory()->create();
        Comment::factory()->for($fiche, 'commentable')->for($commenter)->create();

        $response = $this->actingAs($user)->get(route('my-fiches.index'));

        $response->assertOk();
        $response->assertSee('nieuwe');
    }

    public function test_updates_comments_seen_timestamp(): void
    {
        $user = User::factory()->create();
        $this->assertNull($user->fiches_comments_seen_at);

        $this->actingAs($user)->get(route('my-fiches.index'));

        $user->refresh();
        $this->assertNotNull($user->fiches_comments_seen_at);
    }

    public function test_guest_sees_conversion_cta(): void
    {
        $response = $this->get(route('my-fiches.index'));

        $response->assertOk();
        $response->assertSee('Deel jouw ervaring');
        $response->assertSee('Maak een gratis account');
    }

    public function test_shows_empty_state_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('my-fiches.index'));

        $response->assertOk();
        $response->assertSee('Je hebt nog geen fiches geschreven');
    }

    public function test_lightbulb_icon_shown_for_low_score_fiche_with_suggestions(): void
    {
        $user = User::factory()->create();
        Fiche::factory()->published()
            ->withSuggestions()
            ->withPresentationScore(40)
            ->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('my-fiches.index'))
            ->assertSee('Suggesties beschikbaar');
    }

    public function test_lightbulb_icon_hidden_for_high_score_fiche(): void
    {
        $user = User::factory()->create();
        Fiche::factory()->published()
            ->withSuggestions()
            ->withPresentationScore(80)
            ->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('my-fiches.index'))
            ->assertDontSee('Suggesties beschikbaar');
    }
}
