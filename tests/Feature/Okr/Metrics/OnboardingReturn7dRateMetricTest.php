<?php

namespace Tests\Feature\Okr\Metrics;

use App\Metrics\OnboardingReturn7dRateMetric;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingReturn7dRateMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_compute_as_of_returns_return_rate_for_cohort(): void
    {
        User::factory()->create([
            'created_at' => '2026-04-01',
            'email_verified_at' => '2026-04-01',
            'first_return_at' => '2026-04-03',
            'role' => 'contributor',
        ]);
        User::factory()->create([
            'created_at' => '2026-04-05',
            'email_verified_at' => '2026-04-05',
            'first_return_at' => null,
            'role' => 'contributor',
        ]);

        $metric = new OnboardingReturn7dRateMetric;
        $value = $metric->computeAsOf(CarbonImmutable::parse('2026-04-15'));

        $this->assertSame(50, $value->current);
        $this->assertSame('%', $value->unit);
    }

    public function test_compute_as_of_ignores_returns_after_date(): void
    {
        User::factory()->create([
            'created_at' => '2026-04-01',
            'email_verified_at' => '2026-04-01',
            'first_return_at' => '2026-04-20',
            'role' => 'contributor',
        ]);

        $metric = new OnboardingReturn7dRateMetric;
        $value = $metric->computeAsOf(CarbonImmutable::parse('2026-04-15'));

        $this->assertSame(0, $value->current);
    }
}
