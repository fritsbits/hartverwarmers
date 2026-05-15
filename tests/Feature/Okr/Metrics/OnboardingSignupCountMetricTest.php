<?php

namespace Tests\Feature\Okr\Metrics;

use App\Metrics\OnboardingSignupCountMetric;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingSignupCountMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_compute_as_of_counts_30d_cohort_ending_at_date(): void
    {
        User::factory()->create(['created_at' => '2026-03-15', 'role' => 'contributor']);
        User::factory()->create(['created_at' => '2026-04-01', 'role' => 'contributor']);
        User::factory()->create(['created_at' => '2026-04-10', 'role' => 'contributor']);
        User::factory()->create(['created_at' => '2026-05-01', 'role' => 'contributor']);

        $metric = new OnboardingSignupCountMetric;
        $value = $metric->computeAsOf(CarbonImmutable::parse('2026-04-15'));

        // 30-day window ending 2026-04-15 (>= 2026-03-17 00:00, <= 2026-04-15): 2026-04-01 and 2026-04-10 → 2
        $this->assertSame(2, $value->current);
    }

    public function test_compute_as_of_excludes_admins_and_import_emails(): void
    {
        User::factory()->create(['created_at' => '2026-04-01', 'role' => 'admin']);
        User::factory()->create(['created_at' => '2026-04-01', 'email' => 'x@import.hartverwarmers.be']);
        User::factory()->create(['created_at' => '2026-04-01', 'role' => 'contributor']);

        $metric = new OnboardingSignupCountMetric;
        $value = $metric->computeAsOf(CarbonImmutable::parse('2026-04-15'));

        $this->assertSame(1, $value->current);
    }
}
