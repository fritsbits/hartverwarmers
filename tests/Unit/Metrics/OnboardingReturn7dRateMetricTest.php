<?php

namespace Tests\Unit\Metrics;

use App\Metrics\OnboardingReturn7dRateMetric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingReturn7dRateMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_percentage_of_cohort_with_first_return_at_set(): void
    {
        // 4 verified users in last 30 days, 2 returned
        User::factory()->count(2)->create([
            'created_at' => now()->subDays(10),
            'email_verified_at' => now()->subDays(9),
            'first_return_at' => now()->subDays(5),
        ]);
        User::factory()->count(2)->create([
            'created_at' => now()->subDays(10),
            'email_verified_at' => now()->subDays(9),
            'first_return_at' => null,
        ]);

        $value = (new OnboardingReturn7dRateMetric)->compute('month');

        $this->assertSame(50, $value->current);
        $this->assertSame('%', $value->unit);
    }

    public function test_returns_zero_when_cohort_is_empty(): void
    {
        $value = (new OnboardingReturn7dRateMetric)->compute('month');

        $this->assertSame(0, $value->current);
    }

    public function test_excludes_unverified_users(): void
    {
        // 1 verified user, returned → 100%
        User::factory()->create([
            'created_at' => now()->subDays(5),
            'email_verified_at' => now()->subDays(4),
            'first_return_at' => now()->subDays(2),
        ]);
        // 1 unverified user (should be excluded)
        User::factory()->unverified()->create([
            'created_at' => now()->subDays(5),
            'first_return_at' => now()->subDays(2),
        ]);

        $value = (new OnboardingReturn7dRateMetric)->compute('month');

        $this->assertSame(100, $value->current);
    }

    public function test_excludes_admins(): void
    {
        User::factory()->admin()->create([
            'created_at' => now()->subDays(5),
            'email_verified_at' => now()->subDays(4),
            'first_return_at' => now()->subDays(2),
        ]);

        $value = (new OnboardingReturn7dRateMetric)->compute('month');

        $this->assertSame(0, $value->current);
    }

    public function test_lowdata_flag_when_cohort_under_five(): void
    {
        User::factory()->count(3)->create([
            'created_at' => now()->subDays(5),
            'email_verified_at' => now()->subDays(4),
            'first_return_at' => now()->subDays(2),
        ]);

        $value = (new OnboardingReturn7dRateMetric)->compute('month');

        $this->assertTrue($value->lowData);
    }

    public function test_week_range_uses_7_day_cohort_window(): void
    {
        // verified user 5 days ago, returned
        User::factory()->create([
            'created_at' => now()->subDays(5),
            'email_verified_at' => now()->subDays(4),
            'first_return_at' => now()->subDays(2),
        ]);
        // verified user 20 days ago — outside week cohort
        User::factory()->create([
            'created_at' => now()->subDays(20),
            'email_verified_at' => now()->subDays(19),
            'first_return_at' => now()->subDays(15),
        ]);

        $value = (new OnboardingReturn7dRateMetric)->compute('week');

        $this->assertSame(100, $value->current);  // only the 5-day-old user counts
    }
}
