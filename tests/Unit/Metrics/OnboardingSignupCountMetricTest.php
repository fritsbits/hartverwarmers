<?php

namespace Tests\Unit\Metrics;

use App\Metrics\OnboardingSignupCountMetric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingSignupCountMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_month_range_counts_signups_in_last_30_days_and_previous_30(): void
    {
        // 5 signups in current 30d window
        User::factory()->count(5)->create(['created_at' => now()->subDays(10)]);
        // 2 signups in previous 30d window (30-59 days ago)
        User::factory()->count(2)->create(['created_at' => now()->subDays(40)]);

        $value = (new OnboardingSignupCountMetric)->compute('month');

        $this->assertSame(5, $value->current);
        $this->assertSame(2, $value->previous);
        $this->assertSame(3, $value->delta());
    }

    public function test_alltime_returns_total_count_with_null_previous(): void
    {
        User::factory()->count(7)->create();

        $value = (new OnboardingSignupCountMetric)->compute('alltime');

        $this->assertSame(7, $value->current);
        $this->assertNull($value->previous);
        $this->assertNull($value->delta());
    }

    public function test_excludes_admins_and_stub_emails(): void
    {
        // Counted
        User::factory()->create(['role' => 'contributor', 'created_at' => now()->subDays(5)]);
        // Excluded: admin
        User::factory()->create(['role' => 'admin', 'created_at' => now()->subDays(5)]);
        // Excluded: stub email
        User::factory()->create(['email' => 'someone@import.hartverwarmers.be', 'created_at' => now()->subDays(5)]);

        $value = (new OnboardingSignupCountMetric)->compute('month');

        $this->assertSame(1, $value->current);
    }

    public function test_week_range_uses_seven_day_window(): void
    {
        User::factory()->count(3)->create(['created_at' => now()->subDays(2)]);   // current week
        User::factory()->count(1)->create(['created_at' => now()->subDays(10)]);  // previous week

        $value = (new OnboardingSignupCountMetric)->compute('week');

        $this->assertSame(3, $value->current);
        $this->assertSame(1, $value->previous);
    }
}
