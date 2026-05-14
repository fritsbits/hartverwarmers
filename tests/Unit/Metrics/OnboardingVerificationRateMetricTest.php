<?php

namespace Tests\Unit\Metrics;

use App\Metrics\OnboardingVerificationRateMetric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingVerificationRateMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_month_range_returns_verified_percentage_of_cohort(): void
    {
        // 10 signups in last 30 days, 7 with email_verified_at
        User::factory()->count(7)->create([
            'created_at' => now()->subDays(10),
            'email_verified_at' => now()->subDays(9),
        ]);
        User::factory()->count(3)->unverified()->create(['created_at' => now()->subDays(10)]);

        $value = (new OnboardingVerificationRateMetric)->compute('month');

        $this->assertSame(70, $value->current);
        $this->assertSame('%', $value->unit);
        $this->assertFalse($value->lowData);
    }

    public function test_returns_zero_when_cohort_is_empty(): void
    {
        $value = (new OnboardingVerificationRateMetric)->compute('month');

        $this->assertSame(0, $value->current);
        $this->assertFalse($value->lowData);
    }

    public function test_lowdata_flag_when_cohort_is_under_five(): void
    {
        User::factory()->count(3)->create([
            'created_at' => now()->subDays(5),
            'email_verified_at' => now()->subDays(4),
        ]);

        $value = (new OnboardingVerificationRateMetric)->compute('month');

        $this->assertSame(100, $value->current);
        $this->assertTrue($value->lowData);
    }

    public function test_alltime_uses_all_signups(): void
    {
        User::factory()->count(5)->create([
            'created_at' => now()->subYears(2),
            'email_verified_at' => now()->subYears(2),
        ]);
        User::factory()->count(5)->unverified()->create(['created_at' => now()->subYears(2)]);

        $value = (new OnboardingVerificationRateMetric)->compute('alltime');

        $this->assertSame(50, $value->current);
    }

    public function test_excludes_admins_and_stub_emails_from_cohort(): void
    {
        // 1 contributor verified
        User::factory()->create([
            'role' => 'contributor',
            'created_at' => now()->subDays(5),
            'email_verified_at' => now()->subDays(4),
        ]);
        // Admin — not in cohort
        User::factory()->create([
            'role' => 'admin',
            'created_at' => now()->subDays(5),
            'email_verified_at' => now()->subDays(4),
        ]);
        // Stub email — not in cohort
        User::factory()->create([
            'email' => 'foo@import.hartverwarmers.be',
            'created_at' => now()->subDays(5),
            'email_verified_at' => now()->subDays(4),
        ]);

        $value = (new OnboardingVerificationRateMetric)->compute('month');

        $this->assertSame(100, $value->current);
    }
}
