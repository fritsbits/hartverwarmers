<?php

namespace Tests\Feature\Fiches;

use App\Events\CommentPosted;
use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FicheCommentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_notification_is_queued_when_another_user_comments(): void
    {
        $initiative = Initiative::factory()->create();
        $ficheOwner = User::factory()->create();
        $fiche = Fiche::factory()->for($ficheOwner)->for($initiative)->create(['published' => true]);
        $commenter = User::factory()->create();

        $comment = Comment::create([
            'body' => 'Mooi!',
            'user_id' => $commenter->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'parent_id' => null,
        ]);

        CommentPosted::dispatch($comment);

        $this->assertDatabaseHas('pending_notifications', [
            'user_id' => $ficheOwner->id,
            'type' => 'fiche_comment',
        ]);
    }

    public function test_pending_notification_is_not_queued_when_owner_comments_on_own_fiche(): void
    {
        $initiative = Initiative::factory()->create();
        $ficheOwner = User::factory()->create();
        $fiche = Fiche::factory()->for($ficheOwner)->for($initiative)->create(['published' => true]);

        $comment = Comment::create([
            'body' => 'Mijn eigen reactie.',
            'user_id' => $ficheOwner->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'parent_id' => null,
        ]);

        CommentPosted::dispatch($comment);

        $this->assertDatabaseMissing('pending_notifications', ['user_id' => $ficheOwner->id]);
    }

    public function test_pending_notification_is_not_queued_when_owner_has_opted_out(): void
    {
        $initiative = Initiative::factory()->create();
        $ficheOwner = User::factory()->create(['notification_frequency' => 'never']);
        $fiche = Fiche::factory()->for($ficheOwner)->for($initiative)->create(['published' => true]);
        $commenter = User::factory()->create();

        $comment = Comment::create([
            'body' => 'Test.',
            'user_id' => $commenter->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'parent_id' => null,
        ]);

        CommentPosted::dispatch($comment);

        $this->assertDatabaseMissing('pending_notifications', ['user_id' => $ficheOwner->id]);
    }

    public function test_pending_notification_is_not_queued_when_fiche_owner_is_soft_deleted(): void
    {
        $initiative = Initiative::factory()->create();
        $ficheOwner = User::factory()->create();
        $fiche = Fiche::factory()->for($ficheOwner)->for($initiative)->create(['published' => true]);
        $commenter = User::factory()->create();

        $ficheOwner->delete(); // soft delete

        $comment = Comment::create([
            'body' => 'Test.',
            'user_id' => $commenter->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'parent_id' => null,
        ]);

        CommentPosted::dispatch($comment);

        $this->assertDatabaseMissing('pending_notifications', ['user_id' => $ficheOwner->id]);
    }

    public function test_pending_notification_is_queued_for_reply_on_another_users_fiche(): void
    {
        $initiative = Initiative::factory()->create();
        $ficheOwner = User::factory()->create();
        $fiche = Fiche::factory()->for($ficheOwner)->for($initiative)->create(['published' => true]);
        $commenter = User::factory()->create();

        $parentComment = Comment::create([
            'body' => 'Eerste reactie.',
            'user_id' => $commenter->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'parent_id' => null,
        ]);

        $reply = Comment::create([
            'body' => 'Een antwoord.',
            'user_id' => $commenter->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'parent_id' => $parentComment->id,
        ]);

        CommentPosted::dispatch($reply);

        $this->assertDatabaseHas('pending_notifications', [
            'user_id' => $ficheOwner->id,
            'type' => 'fiche_comment',
        ]);
    }

    public function test_pending_notification_is_not_queued_when_owner_replies_on_own_fiche(): void
    {
        $initiative = Initiative::factory()->create();
        $ficheOwner = User::factory()->create();
        $fiche = Fiche::factory()->for($ficheOwner)->for($initiative)->create(['published' => true]);

        $reply = Comment::create([
            'body' => 'Mijn antwoord.',
            'user_id' => $ficheOwner->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'parent_id' => null,
        ]);

        CommentPosted::dispatch($reply);

        $this->assertDatabaseMissing('pending_notifications', ['user_id' => $ficheOwner->id]);
    }

    public function test_pending_notification_is_queued_for_guest_comment(): void
    {
        $initiative = Initiative::factory()->create();
        $ficheOwner = User::factory()->create();
        $fiche = Fiche::factory()->for($ficheOwner)->for($initiative)->create(['published' => true]);

        $guestUser = User::factory()->create(['first_name' => 'Karen']);
        $this->assertNotEquals($ficheOwner->id, $guestUser->id);

        $comment = Comment::create([
            'body' => 'Interessant!',
            'user_id' => $guestUser->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'parent_id' => null,
        ]);

        CommentPosted::dispatch($comment);

        $this->assertDatabaseHas('pending_notifications', [
            'user_id' => $ficheOwner->id,
            'type' => 'fiche_comment',
        ]);
    }

    public function test_new_user_has_daily_notification_frequency_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertSame('daily', $user->notification_frequency);
    }

    public function test_new_user_via_make_has_daily_notification_frequency(): void
    {
        $user = User::factory()->make();

        $this->assertSame('daily', $user->notification_frequency);
    }
}
