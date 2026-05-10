<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Like;
use App\Models\User;
use App\Models\UserInteraction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DownloadsAndBookmarksControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_receives_thanked_fiche_ids_set(): void
    {
        $user = User::factory()->create();
        $thankedViaKudos = Fiche::factory()->create();
        $thankedViaComment = Fiche::factory()->create();
        $notThanked = Fiche::factory()->create();

        foreach ([$thankedViaKudos, $thankedViaComment, $notThanked] as $fiche) {
            UserInteraction::create([
                'user_id' => $user->id,
                'interactable_type' => Fiche::class,
                'interactable_id' => $fiche->id,
                'type' => 'download',
            ]);
        }

        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $thankedViaKudos->id,
            'type' => 'kudos',
            'count' => 1,
        ]);

        Comment::create([
            'user_id' => $user->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $thankedViaComment->id,
            'body' => 'merci',
            'parent_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('bookmarks.index'))
            ->assertOk()
            ->assertViewHas('thankedFicheIds', function ($ids) use ($thankedViaKudos, $thankedViaComment, $notThanked) {
                return $ids instanceof Collection
                    && $ids->contains($thankedViaKudos->id)
                    && $ids->contains($thankedViaComment->id)
                    && ! $ids->contains($notThanked->id);
            });
    }

    public function test_view_receives_outstanding_thanks_count(): void
    {
        $user = User::factory()->create();
        $thanked = Fiche::factory()->create();
        $notThanked1 = Fiche::factory()->create();
        $notThanked2 = Fiche::factory()->create();

        foreach ([$thanked, $notThanked1, $notThanked2] as $fiche) {
            UserInteraction::create([
                'user_id' => $user->id,
                'interactable_type' => Fiche::class,
                'interactable_id' => $fiche->id,
                'type' => 'download',
            ]);
        }

        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $thanked->id,
            'type' => 'kudos',
            'count' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('bookmarks.index'))
            ->assertOk()
            ->assertViewHas('outstandingThanksCount', 2);
    }
}
