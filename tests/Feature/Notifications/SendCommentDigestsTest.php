<?php

namespace Tests\Feature\Notifications;

use App\Mail\FicheCommentDigestMail;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendCommentDigestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_mail_subject_singular(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
        ]);
        $fiche->load('initiative');

        $mail = new FicheCommentDigestMail($user, $fiche, [
            ['comment_id' => 1, 'body_excerpt' => 'Top!', 'commenter_name' => 'Anna', 'comment_url' => 'https://example.com'],
        ]);

        $this->assertStringContainsString('1 nieuwe reactie', $mail->envelope()->subject);
        $this->assertStringContainsString($fiche->title, $mail->envelope()->subject);
    }

    public function test_mail_subject_plural(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
        ]);
        $fiche->load('initiative');

        $mail = new FicheCommentDigestMail($user, $fiche, [
            ['comment_id' => 1, 'body_excerpt' => 'Super!', 'commenter_name' => 'Anna', 'comment_url' => 'https://example.com'],
            ['comment_id' => 2, 'body_excerpt' => 'Fijn!', 'commenter_name' => 'Jan', 'comment_url' => 'https://example.com'],
        ]);

        $this->assertStringContainsString('2 nieuwe reacties', $mail->envelope()->subject);
    }
}
