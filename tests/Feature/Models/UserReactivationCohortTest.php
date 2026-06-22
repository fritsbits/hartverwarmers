<?php

namespace Tests\Feature\Models;

use App\Models\OnboardingEmailLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserReactivationCohortTest extends TestCase
{
    use RefreshDatabase;

    private function dormant(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => now()->subYears(2),
            'newsletter_unsubscribed_at' => null,
            'last_visited_at' => null,
            'created_at' => now()->subDays(200),
        ], $overrides));
    }

    public function test_includes_a_verified_subscribed_never_visited_old_user(): void
    {
        $user = $this->dormant();

        $this->assertTrue(
            User::reactivationCohort()->whereKey($user->id)->exists()
        );
    }

    public function test_excludes_unverified_unsubscribed_active_and_recent_users(): void
    {
        $unverified = $this->dormant(['email_verified_at' => null]);
        $unsubscribed = $this->dormant(['newsletter_unsubscribed_at' => now()]);
        $active = $this->dormant(['last_visited_at' => now()->subMonth()]);
        $recent = $this->dormant(['created_at' => now()->subDays(10)]);

        $ids = User::reactivationCohort()->pluck('id');

        $this->assertFalse($ids->contains($unverified->id));
        $this->assertFalse($ids->contains($unsubscribed->id));
        $this->assertFalse($ids->contains($active->id));
        $this->assertFalse($ids->contains($recent->id));
    }

    public function test_excludes_users_already_sent_this_campaign(): void
    {
        $sent = $this->dormant();
        OnboardingEmailLog::create([
            'user_id' => $sent->id,
            'mail_key' => config('newsletter.reactivation_mail_key'),
            'sent_at' => now(),
        ]);

        $this->assertFalse(
            User::reactivationCohort()->whereKey($sent->id)->exists()
        );
    }

    public function test_orders_newest_registrations_first(): void
    {
        $older = $this->dormant(['created_at' => now()->subDays(300)]);
        $newer = $this->dormant(['created_at' => now()->subDays(100)]);

        $ids = User::reactivationCohort()->pluck('id')->values();

        $this->assertTrue(
            $ids->search($newer->id) < $ids->search($older->id)
        );
    }
}
