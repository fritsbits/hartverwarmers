<?php

namespace Tests\Feature\Console;

use App\Models\OnboardingEmailLog;
use App\Models\User;
use App\Notifications\ReactivationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendReactivationCampaignTest extends TestCase
{
    use RefreshDatabase;

    private function makeDormant(int $count): void
    {
        User::factory()->count($count)->create([
            'email_verified_at' => now()->subYears(2),
            'last_visited_at' => null,
            'created_at' => now()->subDays(200),
        ]);
    }

    public function test_does_nothing_when_flag_is_off(): void
    {
        config(['newsletter.reactivation_active' => false]);
        $this->makeDormant(3);
        Notification::fake();

        $this->artisan('newsletter:send-reactivation')->assertExitCode(0);

        Notification::assertNothingSent();
        $this->assertSame(0, OnboardingEmailLog::count());
    }

    public function test_sends_first_day_batch_and_logs_each(): void
    {
        config([
            'newsletter.reactivation_active' => true,
            'newsletter.reactivation_ramp' => [2, 5],
        ]);
        $this->makeDormant(4);
        Notification::fake();

        $this->artisan('newsletter:send-reactivation')->assertExitCode(0);

        Notification::assertSentTimes(ReactivationNotification::class, 2);
        $this->assertSame(2, OnboardingEmailLog::where('mail_key', config('newsletter.reactivation_mail_key'))->count());
    }

    public function test_does_not_resend_already_sent_users(): void
    {
        config(['newsletter.reactivation_active' => true, 'newsletter.reactivation_ramp' => [10]]);
        $this->makeDormant(3);
        Notification::fake();

        $this->artisan('newsletter:send-reactivation');
        $this->artisan('newsletter:send-reactivation'); // same day, second run

        // 3 users, batch cap 10, but only 3 distinct sends ever
        Notification::assertSentTimes(ReactivationNotification::class, 3);
        $this->assertSame(3, OnboardingEmailLog::count());
    }

    public function test_skips_users_with_recent_non_exempt_mail(): void
    {
        config(['newsletter.reactivation_active' => true, 'newsletter.reactivation_ramp' => [10]]);
        $this->makeDormant(1);
        $user = User::first();
        OnboardingEmailLog::create([
            'user_id' => $user->id,
            'mail_key' => 'mail_1',
            'sent_at' => now()->subHours(2),
        ]);
        Notification::fake();

        $this->artisan('newsletter:send-reactivation');

        Notification::assertNothingSent();
    }

    public function test_dry_run_sends_nothing_and_logs_nothing(): void
    {
        config(['newsletter.reactivation_active' => true, 'newsletter.reactivation_ramp' => [10]]);
        $this->makeDormant(3);
        Notification::fake();

        $this->artisan('newsletter:send-reactivation --dry-run')->assertExitCode(0);

        Notification::assertNothingSent();
        $this->assertSame(0, OnboardingEmailLog::count());
    }

    public function test_to_option_sends_one_real_email_to_existing_user_without_logging(): void
    {
        config(['newsletter.reactivation_active' => false]); // works even while inactive
        $me = User::factory()->create(['email' => 'me@example.com']);
        $this->makeDormant(3);
        Notification::fake();

        $this->artisan('newsletter:send-reactivation --to=me@example.com')->assertExitCode(0);

        Notification::assertSentTo($me, ReactivationNotification::class);
        Notification::assertSentTimes(ReactivationNotification::class, 1);
        $this->assertSame(0, OnboardingEmailLog::count());
    }

    public function test_to_option_fails_for_unknown_email(): void
    {
        $this->artisan('newsletter:send-reactivation --to=nobody@example.com')->assertExitCode(1);
    }
}
