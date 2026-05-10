<?php

namespace Tests\Feature\Commands;

use App\Models\Fiche;
use App\Models\OnboardingEmailLog;
use App\Models\User;
use App\Models\UserInteraction;
use App\Notifications\OnboardingCuratedActivitiesNotification;
use App\Notifications\OnboardingDownloadMilestoneNotification;
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

    public function test_mail_1_not_sent_to_users_who_verified_more_than_63_days_ago(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(64)]);

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

    public function test_mail_2_sent_when_verified_7_or_more_days_ago_and_mail_1_sent_yesterday(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(7)]);
        OnboardingEmailLog::create(['user_id' => $user->id, 'mail_key' => 'mail_1', 'sent_at' => now()->subDay()]);

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertSentTo($user, OnboardingTopFiveNotification::class);
        $this->assertDatabaseHas('onboarding_email_log', ['user_id' => $user->id, 'mail_key' => 'mail_2']);
    }

    public function test_mail_2_not_sent_when_mail_1_sent_today(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(7)]);
        OnboardingEmailLog::create(['user_id' => $user->id, 'mail_key' => 'mail_1', 'sent_at' => now()]);

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertNotSentTo($user, OnboardingTopFiveNotification::class);
    }

    public function test_mail_2_not_sent_when_mail_1_not_yet_sent(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(7)]);

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertNotSentTo($user, OnboardingTopFiveNotification::class);
    }

    public function test_mail_2_not_sent_when_verified_less_than_7_days_ago(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(6)]);

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertNotSentTo($user, OnboardingTopFiveNotification::class);
    }

    public function test_mail_2_not_sent_to_users_who_verified_more_than_67_days_ago(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(68)]);

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertNotSentTo($user, OnboardingTopFiveNotification::class);
    }

    public function test_mail_2_not_sent_twice(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(10)]);
        OnboardingEmailLog::create(['user_id' => $user->id, 'mail_key' => 'mail_1', 'sent_at' => now()->subDays(7)]);
        OnboardingEmailLog::create(['user_id' => $user->id, 'mail_key' => 'mail_2', 'sent_at' => now()]);

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertNotSentTo($user, OnboardingTopFiveNotification::class);
    }

    // ── Mail 3 ────────────────────────────────────────────────────────────────

    public function test_mail_3_sent_when_user_has_5_or_more_downloads_and_no_fiche(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(1)]);
        $fiches = Fiche::factory()->count(5)->create(['published' => true]);
        foreach ($fiches as $fiche) {
            UserInteraction::create([
                'user_id' => $user->id,
                'interactable_type' => Fiche::class,
                'interactable_id' => $fiche->id,
                'type' => 'download',
            ]);
        }

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertSentTo($user, OnboardingDownloadMilestoneNotification::class);
        $this->assertDatabaseHas('onboarding_email_log', ['user_id' => $user->id, 'mail_key' => 'mail_3']);
    }

    public function test_mail_3_passes_correct_download_count_to_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(1)]);
        $fiches = Fiche::factory()->count(7)->create(['published' => true]);
        foreach ($fiches as $fiche) {
            UserInteraction::create([
                'user_id' => $user->id,
                'interactable_type' => Fiche::class,
                'interactable_id' => $fiche->id,
                'type' => 'download',
            ]);
        }

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertSentTo(
            $user,
            OnboardingDownloadMilestoneNotification::class,
            fn ($notification) => $notification->downloadCount === 7
        );
    }

    public function test_mail_3_not_sent_when_user_has_fewer_than_5_downloads(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(30)]);
        $fiches = Fiche::factory()->count(4)->create(['published' => true]);
        foreach ($fiches as $fiche) {
            UserInteraction::create([
                'user_id' => $user->id,
                'interactable_type' => Fiche::class,
                'interactable_id' => $fiche->id,
                'type' => 'download',
            ]);
        }

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertNotSentTo($user, OnboardingDownloadMilestoneNotification::class);
        $this->assertDatabaseMissing('onboarding_email_log', ['user_id' => $user->id, 'mail_key' => 'mail_3']);
    }

    public function test_mail_3_not_sent_when_user_has_published_fiche(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(1)]);
        Fiche::factory()->for($user)->create(['published' => true]);
        $fiches = Fiche::factory()->count(5)->create(['published' => true]);
        foreach ($fiches as $fiche) {
            UserInteraction::create([
                'user_id' => $user->id,
                'interactable_type' => Fiche::class,
                'interactable_id' => $fiche->id,
                'type' => 'download',
            ]);
        }

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertNotSentTo($user, OnboardingDownloadMilestoneNotification::class);
        // Still logged to prevent re-checking tomorrow
        $this->assertDatabaseHas('onboarding_email_log', ['user_id' => $user->id, 'mail_key' => 'mail_3']);
    }

    public function test_mail_3_not_sent_twice(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()->subDays(1)]);
        $fiches = Fiche::factory()->count(5)->create(['published' => true]);
        foreach ($fiches as $fiche) {
            UserInteraction::create([
                'user_id' => $user->id,
                'interactable_type' => Fiche::class,
                'interactable_id' => $fiche->id,
                'type' => 'download',
            ]);
        }
        OnboardingEmailLog::create(['user_id' => $user->id, 'mail_key' => 'mail_3', 'sent_at' => now()]);

        $this->artisan('onboarding:send-emails')->assertExitCode(0);

        Notification::assertNotSentTo($user, OnboardingDownloadMilestoneNotification::class);
    }
}
