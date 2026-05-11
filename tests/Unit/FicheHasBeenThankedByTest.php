<?php

namespace Tests\Unit;

use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FicheHasBeenThankedByTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_false_when_user_has_no_kudos_and_no_comments(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->create();

        $this->assertFalse($fiche->hasBeenThankedBy($user));
    }

    public function test_returns_true_when_user_has_kudos_with_count_above_zero(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->create();
        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 3,
        ]);

        $this->assertTrue($fiche->hasBeenThankedBy($user));
    }

    public function test_returns_false_when_user_has_kudos_with_zero_count(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->create();
        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 0,
        ]);

        $this->assertFalse($fiche->hasBeenThankedBy($user));
    }

    public function test_returns_true_when_user_has_a_comment(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->create();
        Comment::create([
            'user_id' => $user->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'body' => 'mooi gemaakt',
            'parent_id' => null,
        ]);

        $this->assertTrue($fiche->hasBeenThankedBy($user));
    }

    public function test_returns_false_when_user_only_has_soft_deleted_comments(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->create();
        $comment = Comment::create([
            'user_id' => $user->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'body' => 'mooi gemaakt',
            'parent_id' => null,
        ]);
        $comment->delete();

        $this->assertFalse($fiche->hasBeenThankedBy($user));
    }

    public function test_returns_true_when_user_has_both_kudos_and_comment(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->create();
        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 1,
        ]);
        Comment::create([
            'user_id' => $user->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'body' => 'mooi gemaakt',
            'parent_id' => null,
        ]);

        $this->assertTrue($fiche->hasBeenThankedBy($user));
    }

    public function test_returns_true_for_anonymous_session_with_kudos(): void
    {
        $fiche = Fiche::factory()->create();
        $sessionId = 'anon-session-123';
        Like::create([
            'user_id' => null,
            'session_id' => $sessionId,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 2,
        ]);

        $this->assertTrue($fiche->hasBeenThankedBy(null, $sessionId));
    }

    public function test_returns_false_for_anonymous_session_with_no_data(): void
    {
        $fiche = Fiche::factory()->create();

        $this->assertFalse($fiche->hasBeenThankedBy(null, 'anon-session-456'));
    }

    public function test_does_not_consider_other_users_kudos(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $fiche = Fiche::factory()->create();
        Like::create([
            'user_id' => $other->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 5,
        ]);

        $this->assertFalse($fiche->hasBeenThankedBy($user));
    }

    public function test_does_not_consider_bookmarks(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->create();
        Like::create([
            'user_id' => $user->id,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'bookmark',
        ]);

        $this->assertFalse($fiche->hasBeenThankedBy($user));
    }
}
