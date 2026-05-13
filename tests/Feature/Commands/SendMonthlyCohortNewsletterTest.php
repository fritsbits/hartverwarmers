<?php

namespace Tests\Feature\Commands;

use App\Models\Fiche;
use App\Models\User;
use App\Notifications\MonthlyDigestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
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
}
