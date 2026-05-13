<?php

namespace Tests\Feature\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserNewsletterCohortTest extends TestCase
{
    use RefreshDatabase;

    public function test_qualifies_when_signup_is_exact_30_day_multiple(): void
    {
        Carbon::setTestNow('2026-05-13 08:00:00');

        $user = User::factory()->create(['created_at' => now()->subDays(30)]);

        $this->assertTrue($user->qualifiesForMonthlyDigestToday());
    }

    public function test_qualifies_at_60_days(): void
    {
        Carbon::setTestNow('2026-05-13 08:00:00');

        $user = User::factory()->create(['created_at' => now()->subDays(60)]);

        $this->assertTrue($user->qualifiesForMonthlyDigestToday());
    }

    public function test_does_not_qualify_under_30_days(): void
    {
        Carbon::setTestNow('2026-05-13 08:00:00');

        $user = User::factory()->create(['created_at' => now()->subDays(15)]);

        $this->assertFalse($user->qualifiesForMonthlyDigestToday());
    }

    public function test_does_not_qualify_on_non_multiple_day(): void
    {
        Carbon::setTestNow('2026-05-13 08:00:00');

        $user = User::factory()->create(['created_at' => now()->subDays(31)]);

        $this->assertFalse($user->qualifiesForMonthlyDigestToday());
    }

    public function test_does_not_qualify_if_unverified(): void
    {
        Carbon::setTestNow('2026-05-13 08:00:00');

        $user = User::factory()->unverified()->create(['created_at' => now()->subDays(30)]);

        $this->assertFalse($user->qualifiesForMonthlyDigestToday());
    }

    public function test_does_not_qualify_if_unsubscribed(): void
    {
        Carbon::setTestNow('2026-05-13 08:00:00');

        $user = User::factory()->create([
            'created_at' => now()->subDays(30),
            'newsletter_unsubscribed_at' => now()->subDay(),
        ]);

        $this->assertFalse($user->qualifiesForMonthlyDigestToday());
    }

    public function test_cycle_number_at_30_days_is_1(): void
    {
        Carbon::setTestNow('2026-05-13 08:00:00');

        $user = User::factory()->create(['created_at' => now()->subDays(30)]);

        $this->assertSame(1, $user->currentDigestCycleNumber());
    }

    public function test_cycle_number_at_90_days_is_3(): void
    {
        Carbon::setTestNow('2026-05-13 08:00:00');

        $user = User::factory()->create(['created_at' => now()->subDays(90)]);

        $this->assertSame(3, $user->currentDigestCycleNumber());
    }

    public function test_created_at_is_not_mutated_by_qualifies_check(): void
    {
        Carbon::setTestNow('2026-05-13 14:30:00');

        $user = User::factory()->create(['created_at' => '2026-04-13 09:15:00']);

        $originalCreatedAt = $user->created_at->copy();

        $user->qualifiesForMonthlyDigestToday();
        $user->currentDigestCycleNumber();

        $this->assertTrue(
            $user->created_at->equalTo($originalCreatedAt),
            'created_at should not be mutated by the cohort methods'
        );
    }
}
