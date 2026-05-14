<?php

namespace Tests\Feature\Models;

use App\Models\OnboardingEmailLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserHasRecentNonExemptMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_false_when_user_has_no_email_logs(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasRecentNonExemptMail());
    }

    public function test_returns_true_when_user_received_a_logged_email_in_the_last_24_hours(): void
    {
        Carbon::setTestNow('2026-05-14 12:00:00');

        $user = User::factory()->create();
        OnboardingEmailLog::create([
            'user_id' => $user->id,
            'mail_key' => 'mail_1',
            'sent_at' => now()->subHours(6),
        ]);

        $this->assertTrue($user->hasRecentNonExemptMail());
    }

    public function test_returns_false_when_the_only_log_is_older_than_24_hours(): void
    {
        Carbon::setTestNow('2026-05-14 12:00:00');

        $user = User::factory()->create();
        OnboardingEmailLog::create([
            'user_id' => $user->id,
            'mail_key' => 'mail_1',
            'sent_at' => now()->subHours(25),
        ]);

        $this->assertFalse($user->hasRecentNonExemptMail());
    }

    public function test_returns_true_for_a_milestone_log_within_window(): void
    {
        Carbon::setTestNow('2026-05-14 12:00:00');

        $user = User::factory()->create();
        OnboardingEmailLog::create([
            'user_id' => $user->id,
            'mail_key' => 'mail_4',
            'sent_at' => now()->subMinutes(30),
        ]);

        $this->assertTrue($user->hasRecentNonExemptMail());
    }

    public function test_returns_true_for_a_newsletter_cycle_log_within_window(): void
    {
        Carbon::setTestNow('2026-05-14 12:00:00');

        $user = User::factory()->create();
        OnboardingEmailLog::create([
            'user_id' => $user->id,
            'mail_key' => 'newsletter-cycle-3',
            'sent_at' => now()->subHours(2),
        ]);

        $this->assertTrue($user->hasRecentNonExemptMail());
    }

    public function test_each_users_logs_are_isolated(): void
    {
        Carbon::setTestNow('2026-05-14 12:00:00');

        $alice = User::factory()->create();
        $bob = User::factory()->create();

        OnboardingEmailLog::create([
            'user_id' => $bob->id,
            'mail_key' => 'mail_1',
            'sent_at' => now()->subHour(),
        ]);

        $this->assertFalse($alice->hasRecentNonExemptMail());
        $this->assertTrue($bob->hasRecentNonExemptMail());
    }
}
