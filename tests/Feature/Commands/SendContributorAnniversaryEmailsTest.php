<?php

namespace Tests\Feature\Commands;

use App\Models\Fiche;
use App\Models\OnboardingEmailLog;
use App\Models\User;
use App\Notifications\ContributorAnniversaryNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendContributorAnniversaryEmailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_to_user_on_exact_year_anniversary_of_first_published_fiche(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-14 08:00:00');

        $user = User::factory()->create();
        Fiche::factory()->for($user)->create([
            'published' => true,
            'created_at' => '2025-05-14 14:00:00',
        ]);

        $this->artisan('contributors:send-anniversary-emails')->assertSuccessful();

        Notification::assertSentTo($user, ContributorAnniversaryNotification::class);
        $this->assertDatabaseHas('onboarding_email_log', [
            'user_id' => $user->id,
            'mail_key' => 'anniversary-year-1',
        ]);
    }

    public function test_skips_user_whose_first_fiche_is_a_different_calendar_day(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-14 08:00:00');

        $user = User::factory()->create();
        Fiche::factory()->for($user)->create([
            'published' => true,
            'created_at' => '2025-05-13 14:00:00',
        ]);

        $this->artisan('contributors:send-anniversary-emails')->assertSuccessful();

        Notification::assertNotSentTo($user, ContributorAnniversaryNotification::class);
    }

    public function test_skips_user_whose_first_fiche_was_this_year(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-14 08:00:00');

        $user = User::factory()->create();
        Fiche::factory()->for($user)->create([
            'published' => true,
            'created_at' => '2026-05-14 06:00:00',
        ]);

        $this->artisan('contributors:send-anniversary-emails')->assertSuccessful();

        Notification::assertNotSentTo($user, ContributorAnniversaryNotification::class);
    }

    public function test_uses_earliest_published_fiche_as_anchor_when_user_has_multiple(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-14 08:00:00');

        $user = User::factory()->create();
        Fiche::factory()->for($user)->create(['published' => true, 'created_at' => '2025-05-14 09:00:00']);
        Fiche::factory()->for($user)->create(['published' => true, 'created_at' => '2025-08-01 09:00:00']);

        $this->artisan('contributors:send-anniversary-emails')->assertSuccessful();

        Notification::assertSentTo($user, ContributorAnniversaryNotification::class);
    }

    public function test_ignores_unpublished_fiches_when_computing_anchor(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-14 08:00:00');

        $user = User::factory()->create();
        Fiche::factory()->for($user)->create(['published' => false, 'created_at' => '2025-01-01 09:00:00']);
        Fiche::factory()->for($user)->create(['published' => true, 'created_at' => '2025-05-14 09:00:00']);

        $this->artisan('contributors:send-anniversary-emails')->assertSuccessful();

        Notification::assertSentTo($user, ContributorAnniversaryNotification::class);
    }

    public function test_year_n_computed_correctly_for_3_year_anniversary(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-14 08:00:00');

        $user = User::factory()->create();
        Fiche::factory()->for($user)->create([
            'published' => true,
            'created_at' => '2023-05-14 09:00:00',
        ]);

        $this->artisan('contributors:send-anniversary-emails')->assertSuccessful();

        Notification::assertSentTo($user, ContributorAnniversaryNotification::class, function ($notification) {
            return $notification->year === 3;
        });
        $this->assertDatabaseHas('onboarding_email_log', [
            'user_id' => $user->id,
            'mail_key' => 'anniversary-year-3',
        ]);
    }

    public function test_does_not_send_twice_for_same_year(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-14 08:00:00');

        $user = User::factory()->create();
        Fiche::factory()->for($user)->create([
            'published' => true,
            'created_at' => '2025-05-14 09:00:00',
        ]);
        OnboardingEmailLog::create([
            'user_id' => $user->id,
            'mail_key' => 'anniversary-year-1',
            'sent_at' => now()->subHours(2),
        ]);

        $this->artisan('contributors:send-anniversary-emails')->assertSuccessful();

        Notification::assertNotSentTo($user, ContributorAnniversaryNotification::class);
    }

    public function test_skips_when_user_received_recent_logged_email(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-14 08:00:00');

        $user = User::factory()->create();
        Fiche::factory()->for($user)->create([
            'published' => true,
            'created_at' => '2025-05-14 09:00:00',
        ]);
        OnboardingEmailLog::create([
            'user_id' => $user->id,
            'mail_key' => 'mail_1',
            'sent_at' => now()->subHours(2),
        ]);

        $this->artisan('contributors:send-anniversary-emails')->assertSuccessful();

        Notification::assertNotSentTo($user, ContributorAnniversaryNotification::class);
        $this->assertDatabaseMissing('onboarding_email_log', [
            'user_id' => $user->id,
            'mail_key' => 'anniversary-year-1',
        ]);
    }

    public function test_respects_notify_on_kudos_milestones_preference(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-14 08:00:00');

        $user = User::factory()->create(['notify_on_kudos_milestones' => false]);
        Fiche::factory()->for($user)->create([
            'published' => true,
            'created_at' => '2025-05-14 09:00:00',
        ]);

        $this->artisan('contributors:send-anniversary-emails')->assertSuccessful();

        Notification::assertNotSentTo($user, ContributorAnniversaryNotification::class);
    }

    public function test_skips_unverified_users(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-14 08:00:00');

        $user = User::factory()->unverified()->create();
        Fiche::factory()->for($user)->create([
            'published' => true,
            'created_at' => '2025-05-14 09:00:00',
        ]);

        $this->artisan('contributors:send-anniversary-emails')->assertSuccessful();

        Notification::assertNotSentTo($user, ContributorAnniversaryNotification::class);
    }

    public function test_lenient_on_newsletter_unsubscribe(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-14 08:00:00');

        $user = User::factory()->create(['newsletter_unsubscribed_at' => now()->subDays(30)]);
        Fiche::factory()->for($user)->create([
            'published' => true,
            'created_at' => '2025-05-14 09:00:00',
        ]);

        $this->artisan('contributors:send-anniversary-emails')->assertSuccessful();

        Notification::assertSentTo($user, ContributorAnniversaryNotification::class);
    }

    public function test_leap_year_feb_29_anchor_fires_on_feb_28_in_non_leap_year(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-02-28 08:00:00');

        $user = User::factory()->create();
        Fiche::factory()->for($user)->create([
            'published' => true,
            'created_at' => '2024-02-29 09:00:00',
        ]);

        $this->artisan('contributors:send-anniversary-emails')->assertSuccessful();

        Notification::assertSentTo($user, ContributorAnniversaryNotification::class);
    }

    public function test_for_flag_sends_to_one_user_regardless_of_anniversary_match(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-08-01 08:00:00');

        $user = User::factory()->create(['email' => 'qa@example.com']);
        Fiche::factory()->for($user)->create([
            'published' => true,
            'created_at' => '2025-05-14 09:00:00',
        ]);

        $this->artisan('contributors:send-anniversary-emails', ['--for' => 'qa@example.com'])
            ->assertSuccessful();

        Notification::assertSentTo($user, ContributorAnniversaryNotification::class);
    }

    public function test_for_flag_fails_for_unknown_email(): void
    {
        Notification::fake();

        $this->artisan('contributors:send-anniversary-emails', ['--for' => 'nobody@example.com'])
            ->assertFailed();
    }

    public function test_for_flag_fails_when_target_has_no_published_fiches(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'qa@example.com']);

        $this->artisan('contributors:send-anniversary-emails', ['--for' => 'qa@example.com'])
            ->assertFailed();
    }
}
