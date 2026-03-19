<?php

namespace Tests\Feature\Fiches;

use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use App\Notifications\FicheCommentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
