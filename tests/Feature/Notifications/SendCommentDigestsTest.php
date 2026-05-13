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

    public function test_mail_renders_to_html(): void
    {
        $user = User::factory()->create(['first_name' => 'Frederik']);
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create([
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
            'title' => 'Voorleesnamiddag',
        ]);

        $html = (new FicheCommentDigestMail($user, $fiche, [
            ['comment_id' => 1, 'body_excerpt' => 'Geweldig!', 'commenter_name' => 'Anna', 'comment_url' => 'https://example.com/c/1'],
        ]))->render();

        $this->assertStringContainsString('Frederik', $html);
        $this->assertStringContainsString('Voorleesnamiddag', $html);
        $this->assertStringContainsString('Anna', $html);
        $this->assertStringContainsString('Geweldig!', $html);
        $this->assertStringContainsString('Bekijk alle reacties', $html);
        $this->assertStringContainsString('Uitschrijven', $html);
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

    public function test_deletes_orphan_notifications_when_fiche_is_deleted(): void
    {
        Mail::fake();

        $user = User::factory()->create(['notification_frequency' => 'daily']);
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);
        $this->makePendingNotification($user, $fiche);

        $ficheId = $fiche->id;
        $fiche->forceDelete();

        $this->artisan('notifications:send-digests --frequency=daily');

        $this->assertDatabaseMissing('pending_notifications', [
            'user_id' => $user->id,
            'fiche_id' => $ficheId,
        ]);
    }

    public function test_preserves_pending_notifications_when_mailer_throws(): void
    {
        $user = User::factory()->create(['notification_frequency' => 'daily']);
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $user->id, 'initiative_id' => $initiative->id]);
        $this->makePendingNotification($user, $fiche);

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Mail server down'));

        $this->artisan('notifications:send-digests --frequency=daily')->assertExitCode(0);

        $this->assertDatabaseHas('pending_notifications', ['user_id' => $user->id]);
    }

    public function test_continues_processing_other_users_when_one_send_fails(): void
    {
        $failingUser = User::factory()->create(['notification_frequency' => 'daily']);
        $succeedingUser = User::factory()->create(['notification_frequency' => 'daily']);
        $initiative = Initiative::factory()->published()->create();
        $failingFiche = Fiche::factory()->published()->create(['user_id' => $failingUser->id, 'initiative_id' => $initiative->id]);
        $succeedingFiche = Fiche::factory()->published()->create(['user_id' => $succeedingUser->id, 'initiative_id' => $initiative->id]);
        $this->makePendingNotification($failingUser, $failingFiche);
        $this->makePendingNotification($succeedingUser, $succeedingFiche);

        Mail::shouldReceive('to')
            ->with(\Mockery::on(fn ($u) => $u->id === $failingUser->id))
            ->andThrow(new \RuntimeException('Send failed'));

        $pendingMailable = \Mockery::mock();
        $pendingMailable->shouldReceive('send')->once();
        Mail::shouldReceive('to')
            ->with(\Mockery::on(fn ($u) => $u->id === $succeedingUser->id))
            ->andReturn($pendingMailable);

        $this->artisan('notifications:send-digests --frequency=daily')->assertExitCode(0);

        $this->assertDatabaseHas('pending_notifications', ['user_id' => $failingUser->id]);
        $this->assertDatabaseMissing('pending_notifications', ['user_id' => $succeedingUser->id]);
    }
}
