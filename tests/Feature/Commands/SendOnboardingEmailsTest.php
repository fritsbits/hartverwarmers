<?php

namespace Tests\Feature\Commands;

use App\Models\Fiche;
use App\Models\OnboardingEmailLog;
use App\Models\User;
use App\Notifications\OnboardingContributeInvitationNotification;
use App\Notifications\OnboardingCuratedActivitiesNotification;
use App\Notifications\OnboardingTopFiveNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendOnboardingEmailsTest extends TestCase
{
    use RefreshDatabase;

    // ── Mail 1 ────────────────────────────────────────────────────────────────

    public function test_mail_1_sent_when_verified_3_or_more_days_ago(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(3)]);

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertSentTo($user, OnboardingCuratedActivitiesNotification::class);
        $this->assertDatabaseHas('onboarding_email_log', ['user_id' => $user->id, 'mail_key' => 'mail_1']);
    }

    public function test_mail_1_not_sent_when_verified_less_than_3_days_ago(): void
    {
        Notification::fake();
        User::factory()->create(['email_verified_at' => now()->subDays(2)]);

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_mail_1_not_sent_twice(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(4)]);
        OnboardingEmailLog::create(['user_id' => $user->id, 'mail_key' => 'mail_1', 'sent_at' => now()]);

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertNotSentTo($user, OnboardingCuratedActivitiesNotification::class);
    }

    public function test_mail_1_not_sent_to_users_who_opted_out(): void
    {
        Notification::fake();
        User::factory()->create([
            'email_verified_at' => now()->subDays(4),
            'notify_on_onboarding_emails' => false,
        ]);

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    // ── Mail 2 ────────────────────────────────────────────────────────────────

    public function test_mail_2_sent_when_verified_7_or_more_days_ago(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(7)]);

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertSentTo($user, OnboardingTopFiveNotification::class);
        $this->assertDatabaseHas('onboarding_email_log', ['user_id' => $user->id, 'mail_key' => 'mail_2']);
    }

    public function test_mail_2_not_sent_when_verified_less_than_7_days_ago(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(6)]);

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertNotSentTo($user, OnboardingTopFiveNotification::class);
    }

    public function test_mail_2_not_sent_twice(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(10)]);
        OnboardingEmailLog::create(['user_id' => $user->id, 'mail_key' => 'mail_2', 'sent_at' => now()]);

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertNotSentTo($user, OnboardingTopFiveNotification::class);
    }

    // ── Mail 3 ────────────────────────────────────────────────────────────────

    public function test_mail_3_sent_when_verified_14_or_more_days_ago_and_no_fiche(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(14)]);

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertSentTo($user, OnboardingContributeInvitationNotification::class);
        $this->assertDatabaseHas('onboarding_email_log', ['user_id' => $user->id, 'mail_key' => 'mail_3']);
    }

    public function test_mail_3_not_sent_when_user_has_published_fiche(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(14)]);
        Fiche::factory()->for($user)->create(['published' => true]);

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertNotSentTo($user, OnboardingContributeInvitationNotification::class);
        // Still logged to prevent re-checking tomorrow
        $this->assertDatabaseHas('onboarding_email_log', ['user_id' => $user->id, 'mail_key' => 'mail_3']);
    }

    public function test_mail_3_not_sent_twice(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(20)]);
        OnboardingEmailLog::create(['user_id' => $user->id, 'mail_key' => 'mail_3', 'sent_at' => now()]);

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertNotSentTo($user, OnboardingContributeInvitationNotification::class);
    }

    public function test_mail_3_not_sent_when_verified_less_than_14_days_ago(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(13)]);

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertNotSentTo($user, OnboardingContributeInvitationNotification::class);
    }
}
