<?php

namespace Tests\Feature\Commands;

use App\Models\Fiche;
use App\Models\OnboardingEmailLog;
use App\Models\ProductUpdateSend;
use App\Models\User;
use App\Notifications\MonthlyDigestNotification;
use App\Services\MonthlyDigest\Composer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class SendMonthlyCohortNewsletterTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_to_users_at_30_day_signup_anniversary(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-13 08:00:00');

        Fiche::factory()->published()->create(); // make payload non-empty

        $eligible = User::factory()->create(['created_at' => now()->subDays(30)]);
        $tooNew = User::factory()->create(['created_at' => now()->subDays(15)]);
        $offCycle = User::factory()->create(['created_at' => now()->subDays(31)]);
        $unsubscribed = User::factory()->create([
            'created_at' => now()->subDays(60),
            'newsletter_unsubscribed_at' => now(),
        ]);

        $this->artisan('newsletter:send-monthly-cohort')->assertSuccessful();

        Notification::assertSentTo($eligible, MonthlyDigestNotification::class);
        Notification::assertNotSentTo($tooNew, MonthlyDigestNotification::class);
        Notification::assertNotSentTo($offCycle, MonthlyDigestNotification::class);
        Notification::assertNotSentTo($unsubscribed, MonthlyDigestNotification::class);
    }

    public function test_skips_all_sends_when_payload_globally_empty(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-13 08:00:00');

        // No themes, no fiches — payload is empty for everyone
        $user = User::factory()->create(['created_at' => now()->subDays(30)]);

        $this->artisan('newsletter:send-monthly-cohort')->assertSuccessful();

        Notification::assertNotSentTo($user, MonthlyDigestNotification::class);
    }

    public function test_idempotency_key_is_attached_to_notification(): void
    {
        Carbon::setTestNow('2026-05-13 08:00:00');

        $user = User::factory()->create(['created_at' => now()->subDays(60)]);

        Fiche::factory()->published()->create();

        Notification::fake();
        $this->artisan('newsletter:send-monthly-cohort')->assertSuccessful();

        Notification::assertSentTo($user, MonthlyDigestNotification::class, function ($notification) use ($user) {
            return $notification->idempotencyKey($user) === "digest-{$user->id}-cycle-2";
        });
    }

    public function test_idempotency_key_lands_on_symfony_message_header(): void
    {
        Carbon::setTestNow('2026-05-13 08:00:00');

        Fiche::factory()->published()->create();

        $user = User::factory()->create(['created_at' => now()->subDays(30)]);

        $payload = app(Composer::class)->compose(now());
        $mail = (new MonthlyDigestNotification($payload, cycle: 1))->toMail($user);

        $symfonyMessage = new Email;
        foreach ($mail->callbacks as $callback) {
            $callback($symfonyMessage);
        }

        $this->assertSame(
            "digest-{$user->id}-cycle-1",
            $symfonyMessage->getHeaders()->get('Idempotency-Key')?->getBodyAsString()
        );
    }

    public function test_for_flag_sends_only_to_one_address(): void
    {
        Notification::fake();

        $target = User::factory()->create(['email' => 'qa@example.com', 'created_at' => now()->subDays(60)]);
        $other = User::factory()->create(['created_at' => now()->subDays(30)]);

        Fiche::factory()->published()->create();

        $this->artisan('newsletter:send-monthly-cohort', ['--for' => 'qa@example.com'])
            ->assertSuccessful();

        Notification::assertSentTo($target, MonthlyDigestNotification::class);
        Notification::assertNotSentTo($other, MonthlyDigestNotification::class);
    }

    public function test_for_flag_with_unknown_email_fails(): void
    {
        Notification::fake();

        $this->artisan('newsletter:send-monthly-cohort', ['--for' => 'nobody@example.com'])
            ->assertFailed();
    }

    public function test_newsletter_skipped_when_user_received_a_logged_email_in_last_24h(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-13 08:00:00');

        Fiche::factory()->published()->create();

        $user = User::factory()->create(['created_at' => now()->subDays(30)]);
        OnboardingEmailLog::create([
            'user_id' => $user->id,
            'mail_key' => 'mail_1',
            'sent_at' => now()->subHours(3),
        ]);

        $this->artisan('newsletter:send-monthly-cohort')->assertSuccessful();

        Notification::assertNotSentTo($user, MonthlyDigestNotification::class);
        $this->assertDatabaseMissing('onboarding_email_log', ['user_id' => $user->id, 'mail_key' => 'newsletter-cycle-1']);
    }

    public function test_newsletter_send_logs_a_cycle_row_in_onboarding_email_log(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-13 08:00:00');

        Fiche::factory()->published()->create();

        $user = User::factory()->create(['created_at' => now()->subDays(60)]);

        $this->artisan('newsletter:send-monthly-cohort')->assertSuccessful();

        Notification::assertSentTo($user, MonthlyDigestNotification::class);
        $this->assertDatabaseHas('onboarding_email_log', [
            'user_id' => $user->id,
            'mail_key' => 'newsletter-cycle-2',
        ]);
    }

    public function test_diamond_is_never_repeated_for_a_user_in_consecutive_digests(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-13 08:00:00');

        $diamond = Fiche::factory()->published()->withDiamond()->create();
        Fiche::factory()->published()->create();

        $sawItLastCycle = User::factory()->create(['created_at' => now()->subDays(30)]);
        $sawItLastCycle->forceFill(['last_digest_diamond_fiche_id' => $diamond->id])->saveQuietly();

        $neverSawIt = User::factory()->create(['created_at' => now()->subDays(60)]);

        $this->artisan('newsletter:send-monthly-cohort')->assertSuccessful();

        Notification::assertSentTo(
            $sawItLastCycle,
            MonthlyDigestNotification::class,
            fn (MonthlyDigestNotification $notification) => $notification->payload->diamond === null
        );
        Notification::assertSentTo(
            $neverSawIt,
            MonthlyDigestNotification::class,
            fn (MonthlyDigestNotification $notification) => $notification->payload->diamond?->id === $diamond->id
        );
    }

    public function test_shown_diamond_is_recorded_on_the_user(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-13 08:00:00');

        $diamond = Fiche::factory()->published()->withDiamond()->create();
        Fiche::factory()->published()->create();

        $user = User::factory()->create(['created_at' => now()->subDays(30)]);

        $this->artisan('newsletter:send-monthly-cohort')->assertSuccessful();

        $this->assertSame($diamond->id, $user->fresh()->last_digest_diamond_fiche_id);
    }

    private function putProductUpdate(string $uid, string $publishedAt): void
    {
        Storage::disk('content')->put("updates/{$uid}.json", json_encode([
            'uid' => $uid,
            'published_at' => $publishedAt,
            'title' => "Titel {$uid}",
            'body' => 'Korte tekst.',
            'link' => ['url' => '/themas', 'label' => 'Bekijk de themakalender'],
        ]));
    }

    public function test_product_update_is_included_and_recorded_for_a_user_who_never_saw_it(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-13 08:00:00');
        Storage::fake('content');
        $this->putProductUpdate('2026-05-vers', '2026-05-01');

        Fiche::factory()->published()->create();
        $user = User::factory()->create(['created_at' => now()->subDays(30)]);

        $this->artisan('newsletter:send-monthly-cohort')->assertSuccessful();

        Notification::assertSentTo(
            $user,
            MonthlyDigestNotification::class,
            fn (MonthlyDigestNotification $notification) => $notification->payload->productUpdate['uid'] === '2026-05-vers'
        );
        $this->assertDatabaseHas('product_update_sends', [
            'user_id' => $user->id,
            'update_uid' => '2026-05-vers',
        ]);
    }

    public function test_product_update_is_never_sent_twice_to_the_same_user(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-13 08:00:00');
        Storage::fake('content');
        $this->putProductUpdate('2026-05-vers', '2026-05-01');

        Fiche::factory()->published()->create();
        $user = User::factory()->create(['created_at' => now()->subDays(60)]);
        ProductUpdateSend::create(['user_id' => $user->id, 'update_uid' => '2026-05-vers', 'sent_at' => now()->subDays(30)]);

        $this->artisan('newsletter:send-monthly-cohort')->assertSuccessful();

        Notification::assertSentTo(
            $user,
            MonthlyDigestNotification::class,
            fn (MonthlyDigestNotification $notification) => $notification->payload->productUpdate === null
        );
        $this->assertDatabaseCount('product_update_sends', 1);
    }

    public function test_user_who_saw_update_a_receives_update_b_in_a_later_cycle(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-13 08:00:00');
        Storage::fake('content');
        $this->putProductUpdate('2026-04-update-a', '2026-04-01');
        $this->putProductUpdate('2026-05-update-b', '2026-05-01');

        Fiche::factory()->published()->create();
        $user = User::factory()->create(['created_at' => now()->subDays(60)]);
        ProductUpdateSend::create(['user_id' => $user->id, 'update_uid' => '2026-04-update-a', 'sent_at' => now()->subDays(30)]);

        $this->artisan('newsletter:send-monthly-cohort')->assertSuccessful();

        Notification::assertSentTo(
            $user,
            MonthlyDigestNotification::class,
            fn (MonthlyDigestNotification $notification) => $notification->payload->productUpdate['uid'] === '2026-05-update-b'
        );
    }

    public function test_digest_still_sends_when_no_fresh_product_update_exists(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-13 08:00:00');
        Storage::fake('content');

        Fiche::factory()->published()->create();
        $user = User::factory()->create(['created_at' => now()->subDays(30)]);

        $this->artisan('newsletter:send-monthly-cohort')->assertSuccessful();

        Notification::assertSentTo(
            $user,
            MonthlyDigestNotification::class,
            fn (MonthlyDigestNotification $notification) => $notification->payload->productUpdate === null
        );
        $this->assertDatabaseCount('product_update_sends', 0);
    }

    public function test_email_renders_product_update_section(): void
    {
        Carbon::setTestNow('2026-05-13 08:00:00');
        Storage::fake('content');
        $this->putProductUpdate('2026-05-vers', '2026-05-01');

        $user = User::factory()->create();
        $payload = app(Composer::class)->compose(now());

        $html = view('emails.monthly-digest', [
            'payload' => $payload,
            'notifiable' => $user,
            'previewText' => 'test',
        ])->render();

        $this->assertStringContainsString('Nieuw op Hartverwarmers', $html);
        $this->assertStringContainsString('Titel 2026-05-vers', $html);
        $this->assertStringContainsString('Bekijk de themakalender', $html);
    }
}
