<?php

namespace Tests\Feature\Notifications;

use App\Events\CommentPosted;
use App\Listeners\SendFicheCommentNotification;
use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\PendingNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendFicheCommentNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeComment(User $owner, string $frequency = 'daily'): Comment
    {
        $owner->update(['notification_frequency' => $frequency]);
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'user_id' => $owner->id,
            'initiative_id' => $initiative->id,
        ]);
        $commenter = User::factory()->create();

        return Comment::factory()->create([
            'user_id' => $commenter->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
        ]);
    }

    public function test_creates_pending_notification_for_daily_user(): void
    {
        $owner = User::factory()->create();
        $comment = $this->makeComment($owner, 'daily');

        (new SendFicheCommentNotification)->handle(new CommentPosted($comment));

        $this->assertDatabaseHas('pending_notifications', [
            'user_id' => $owner->id,
            'type' => 'fiche_comment',
        ]);
    }

    public function test_creates_pending_notification_for_weekly_user(): void
    {
        $owner = User::factory()->create();
        $comment = $this->makeComment($owner, 'weekly');

        (new SendFicheCommentNotification)->handle(new CommentPosted($comment));

        $this->assertDatabaseHas('pending_notifications', [
            'user_id' => $owner->id,
            'type' => 'fiche_comment',
        ]);
    }

    public function test_does_not_create_pending_notification_when_frequency_is_never(): void
    {
        $owner = User::factory()->create();
        $comment = $this->makeComment($owner, 'never');

        (new SendFicheCommentNotification)->handle(new CommentPosted($comment));

        $this->assertDatabaseMissing('pending_notifications', ['user_id' => $owner->id]);
    }

    public function test_does_not_create_pending_notification_when_commenter_is_owner(): void
    {
        $owner = User::factory()->create(['notification_frequency' => 'daily']);
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'user_id' => $owner->id,
            'initiative_id' => $initiative->id,
        ]);
        $comment = Comment::factory()->create([
            'user_id' => $owner->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
        ]);

        (new SendFicheCommentNotification)->handle(new CommentPosted($comment));

        $this->assertDatabaseMissing('pending_notifications', ['user_id' => $owner->id]);
    }

    public function test_payload_contains_required_fields(): void
    {
        $owner = User::factory()->create();
        $comment = $this->makeComment($owner, 'daily');

        (new SendFicheCommentNotification)->handle(new CommentPosted($comment));

        $notification = PendingNotification::where('user_id', $owner->id)->first();
        $this->assertNotNull($notification);
        $this->assertArrayHasKey('comment_id', $notification->payload);
        $this->assertArrayHasKey('body_excerpt', $notification->payload);
        $this->assertArrayHasKey('commenter_name', $notification->payload);
        $this->assertArrayHasKey('comment_url', $notification->payload);
    }

    public function test_body_excerpt_escapes_markdown_characters(): void
    {
        $owner = User::factory()->create(['notification_frequency' => 'daily']);
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'user_id' => $owner->id,
            'initiative_id' => $initiative->id,
        ]);
        $commenter = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $commenter->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'body' => 'Klik [hier](https://evil.example.com) **nu** of `voer code uit`',
        ]);

        (new SendFicheCommentNotification)->handle(new CommentPosted($comment));

        $excerpt = PendingNotification::where('user_id', $owner->id)->first()->payload['body_excerpt'];

        $this->assertStringNotContainsString('[hier](', $excerpt);
        $this->assertStringNotContainsString('**nu**', $excerpt);
        $this->assertStringContainsString('\\[hier\\]', $excerpt);
        $this->assertStringContainsString('\\*\\*nu\\*\\*', $excerpt);
        $this->assertStringContainsString('\\`voer code uit\\`', $excerpt);
    }
}
