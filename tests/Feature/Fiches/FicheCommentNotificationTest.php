<?php

namespace Tests\Feature\Fiches;

use App\Events\CommentPosted;
use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use App\Notifications\FicheCommentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FicheCommentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_has_correct_subject_and_greeting(): void
    {
        $initiative = Initiative::factory()->create();
        $ficheOwner = User::factory()->create(['first_name' => 'Marie']);
        $fiche = Fiche::factory()->for($ficheOwner)->for($initiative)->create(['published' => true]);
        $commenter = User::factory()->create(['first_name' => 'Liesbet']);
        $comment = Comment::create([
            'body' => 'Mooi initiatief!',
            'user_id' => $commenter->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'parent_id' => null,
        ]);

        $mail = (new FicheCommentNotification($comment))->toMail($ficheOwner);

        $this->assertStringContainsString("Nieuwe reactie op je fiche: {$fiche->title}", $mail->subject);
        $this->assertStringContainsString('Hoi Marie!', $mail->greeting);
        $rendered = $mail->render()->toHtml();
        $this->assertStringContainsString('Liesbet', $rendered);
    }

    public function test_notification_is_sent_when_another_user_comments(): void
    {
        Notification::fake();

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

        Notification::assertSentTo($ficheOwner, FicheCommentNotification::class);
    }

    public function test_notification_is_not_sent_when_owner_comments_on_own_fiche(): void
    {
        Notification::fake();

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

        Notification::assertNothingSent();
    }

    public function test_notification_is_not_sent_when_owner_has_opted_out(): void
    {
        Notification::fake();

        $initiative = Initiative::factory()->create();
        $ficheOwner = User::factory()->create(['notify_on_fiche_comments' => false]);
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

        Notification::assertNothingSent();
    }

    public function test_notification_is_not_sent_when_fiche_owner_is_soft_deleted(): void
    {
        Notification::fake();

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

        Notification::assertNothingSent();
    }

    public function test_notification_is_sent_for_reply_on_another_users_fiche(): void
    {
        Notification::fake();

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

        Notification::assertSentTo($ficheOwner, FicheCommentNotification::class);
    }

    public function test_notification_is_not_sent_when_owner_replies_on_own_fiche(): void
    {
        Notification::fake();

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

        Notification::assertNothingSent();
    }

    public function test_notification_is_sent_for_guest_comment(): void
    {
        Notification::fake();

        $initiative = Initiative::factory()->create();
        $ficheOwner = User::factory()->create();
        $fiche = Fiche::factory()->for($ficheOwner)->for($initiative)->create(['published' => true]);

        // Simulate what addGuestComment does: a fresh user is created, distinct from the fiche owner
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

        Notification::assertSentTo($ficheOwner, FicheCommentNotification::class);
    }

    public function test_new_user_has_notify_on_fiche_comments_true_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->notify_on_fiche_comments);
    }

    public function test_new_user_via_make_has_notify_on_fiche_comments_true(): void
    {
        $user = User::factory()->make();

        $this->assertTrue($user->notify_on_fiche_comments);
    }
}
