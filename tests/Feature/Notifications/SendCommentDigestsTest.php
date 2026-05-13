<?php

namespace Tests\Feature\Notifications;

use App\Mail\FicheCommentDigestMail;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\PendingNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

    private function makePendingNotification(User $user, Fiche $fiche): void
    {
        PendingNotification::create([
            'user_id' => $user->id,
            'type' => 'fiche_comment',
            'fiche_id' => $fiche->id,
            'payload' => [
                'comment_id' => 1,
                'body_excerpt' => 'Geweldig werk!',
                'commenter_name' => 'Anna',
                'comment_url' => 'https://example.com',
            ],
        ]);
    }

    public function test_sends_digest_to_daily_users_on_daily_run(): void
    {
        Mail::fake();

        $user = User::factory()->create(['notification_frequency' => 'daily']);
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);
        $this->makePendingNotification($user, $fiche);

        $this->artisan('notifications:send-digests --frequency=daily');

        Mail::assertSent(FicheCommentDigestMail::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_does_not_send_digest_to_weekly_users_on_daily_run(): void
    {
        Mail::fake();

        $user = User::factory()->create(['notification_frequency' => 'weekly']);
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);
        $this->makePendingNotification($user, $fiche);

        $this->artisan('notifications:send-digests --frequency=daily');

        Mail::assertNotSent(FicheCommentDigestMail::class);
    }

    public function test_sends_separate_emails_per_fiche(): void
    {
        Mail::fake();

        $user = User::factory()->create(['notification_frequency' => 'daily']);
        $initiative = Initiative::factory()->published()->create();
        $fiche1 = Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);
        $fiche2 = Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);
        $this->makePendingNotification($user, $fiche1);
        $this->makePendingNotification($user, $fiche2);

        $this->artisan('notifications:send-digests --frequency=daily');

        Mail::assertSentCount(2);
    }

    public function test_deletes_pending_notifications_after_sending(): void
    {
        Mail::fake();

        $user = User::factory()->create(['notification_frequency' => 'daily']);
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);
        $this->makePendingNotification($user, $fiche);

        $this->artisan('notifications:send-digests --frequency=daily');

        $this->assertDatabaseMissing('pending_notifications', ['user_id' => $user->id]);
    }

    public function test_sends_weekly_digest_to_weekly_users(): void
    {
        Mail::fake();

        $user = User::factory()->create(['notification_frequency' => 'weekly']);
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);
        $this->makePendingNotification($user, $fiche);

        $this->artisan('notifications:send-digests --frequency=weekly');

        Mail::assertSent(FicheCommentDigestMail::class, fn ($mail) => $mail->hasTo($user->email));
    }
}
