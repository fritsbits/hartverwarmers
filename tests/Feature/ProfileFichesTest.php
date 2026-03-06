<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileFichesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_sees_own_fiches(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->published()->create([
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
            'title' => 'Mijn test fiche',
        ]);

        $response = $this->actingAs($user)->get(route('profile.fiches'));

        $response->assertOk();
        $response->assertSee('Mijn test fiche');
    }

    public function test_other_users_fiches_are_not_shown(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $initiative = Initiative::factory()->create();

        Fiche::factory()->published()->create([
            'user_id' => $otherUser->id,
            'initiative_id' => $initiative->id,
            'title' => 'Andermans fiche',
        ]);

        $response = $this->actingAs($user)->get(route('profile.fiches'));

        $response->assertOk();
        $response->assertDontSee('Andermans fiche');
    }

    public function test_aggregate_stats_are_correct(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create();

        Fiche::factory()->published()->create([
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
            'download_count' => 10,
            'kudos_count' => 5,
        ]);
        Fiche::factory()->create([
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
            'published' => false,
            'download_count' => 3,
            'kudos_count' => 2,
        ]);

        $response = $this->actingAs($user)->get(route('profile.fiches'));

        $response->assertOk();
        $response->assertViewHas('stats', [
            'total' => 2,
            'published' => 1,
            'drafts' => 1,
            'downloads' => 13,
            'kudos' => 7,
            'comments' => 0,
        ]);
    }

    public function test_new_comments_count_works(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->published()->create([
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
        ]);

        $commenter = User::factory()->create();
        Comment::factory()->create([
            'user_id' => $commenter->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
        ]);

        $response = $this->actingAs($user)->get(route('profile.fiches'));

        $response->assertOk();
        $response->assertViewHas('newCommentsCount', 1);
    }

    public function test_visiting_page_resets_comments_seen_at(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->fiches_comments_seen_at);

        $this->actingAs($user)->get(route('profile.fiches'));

        $user->refresh();
        $this->assertNotNull($user->fiches_comments_seen_at);
    }

    public function test_empty_state_renders_for_user_with_no_fiches(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.fiches'));

        $response->assertOk();
        $response->assertSee('Je hebt nog geen fiches geschreven.');
        $response->assertSee('Schrijf je eerste fiche');
    }

    public function test_unauthenticated_user_gets_redirected(): void
    {
        $response = $this->get(route('profile.fiches'));

        $response->assertRedirect(route('login'));
    }
}
