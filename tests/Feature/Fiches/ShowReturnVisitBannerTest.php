<?php

namespace Tests\Feature\Fiches;

use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Like;
use App\Models\User;
use App\Models\UserInteraction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowReturnVisitBannerTest extends TestCase
{
    use RefreshDatabase;

    private function createDownloadedFiche(?User $user = null): array
    {
        $initiative = Initiative::factory()->create(['published' => true]);
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        if ($user) {
            UserInteraction::create([
                'user_id' => $user->id,
                'interactable_type' => Fiche::class,
                'interactable_id' => $fiche->id,
                'type' => 'download',
                'created_at' => now()->subDay(),
            ]);
        }

        return [$initiative, $fiche];
    }

    public function test_banner_shows_when_user_downloaded_but_not_thanked(): void
    {
        $user = User::factory()->create();
        [$initiative, $fiche] = $this->createDownloadedFiche($user);

        $this->actingAs($user)
            ->get(route('fiches.show', [$initiative, $fiche]))
            ->assertOk()
            ->assertSee('Je downloadde')
            ->assertSee('Bedank '.$fiche->user->first_name);
    }

    public function test_banner_hidden_when_user_already_gave_kudos(): void
    {
        $user = User::factory()->create();
        [$initiative, $fiche] = $this->createDownloadedFiche($user);
        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('fiches.show', [$initiative, $fiche]))
            ->assertOk()
            ->assertDontSee('Je downloadde');
    }

    public function test_banner_hidden_when_user_already_commented(): void
    {
        $user = User::factory()->create();
        [$initiative, $fiche] = $this->createDownloadedFiche($user);
        Comment::create([
            'user_id' => $user->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'body' => 'mooi',
            'parent_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('fiches.show', [$initiative, $fiche]))
            ->assertOk()
            ->assertDontSee('Je downloadde');
    }

    public function test_banner_hidden_when_user_did_not_download(): void
    {
        $user = User::factory()->create();
        [$initiative, $fiche] = $this->createDownloadedFiche();

        $this->actingAs($user)
            ->get(route('fiches.show', [$initiative, $fiche]))
            ->assertOk()
            ->assertDontSee('Je downloadde');
    }

    public function test_banner_hidden_when_viewing_own_fiche(): void
    {
        $user = User::factory()->create();
        [$initiative, $fiche] = $this->createDownloadedFiche($user);
        $fiche->update(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('fiches.show', [$initiative, $fiche]))
            ->assertOk()
            ->assertDontSee('Je downloadde');
    }

    public function test_banner_hidden_when_unauthenticated(): void
    {
        [$initiative, $fiche] = $this->createDownloadedFiche();

        $this->get(route('fiches.show', [$initiative, $fiche]))
            ->assertOk()
            ->assertDontSee('Je downloadde');
    }
}
