<?php

namespace Tests\Feature\Okr\Metrics;

use App\Metrics\OnboardingVerificationRateMetric;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingVerificationRateMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_compute_as_of_returns_verification_rate_for_cohort_ending_at_date(): void
    {
        User::factory()->create([
            'created_at' => '2026-04-01',
            'email_verified_at' => '2026-04-02',
            'role' => 'contributor',
        ]);
        User::factory()->create([
            'created_at' => '2026-04-05',
            'email_verified_at' => null,
            'role' => 'contributor',
        ]);
        User::factory()->create([
            'created_at' => '2026-02-01',
            'email_verified_at' => '2026-02-02',
            'role' => 'contributor',
        ]);

        $metric = new OnboardingVerificationRateMetric;
        $value = $metric->computeAsOf(CarbonImmutable::parse('2026-04-15'));

        $this->assertSame(50, $value->current);
        $this->assertSame('%', $value->unit);
    }

    public function test_compute_as_of_excludes_verifications_after_date(): void
    {
        User::factory()->create([
            'created_at' => '2026-04-01',
            'email_verified_at' => '2026-04-20',
            'role' => 'contributor',
        ]);

        $metric = new OnboardingVerificationRateMetric;
        $value = $metric->computeAsOf(CarbonImmutable::parse('2026-04-15'));

        $this->assertSame(0, $value->current);
    }
}
