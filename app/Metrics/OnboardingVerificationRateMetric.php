<?php

namespace App\Metrics;

use App\Models\User;
use App\Services\Okr\Metric;
use App\Services\Okr\MetricValue;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class OnboardingVerificationRateMetric implements Metric
{
    public function compute(string $range): MetricValue
    {
        $base = $this->signupCohortQuery();

        $cohort = match ($range) {
            'week' => (clone $base)->where('created_at', '>=', now()->subDays(6)->startOfDay()),
            'quarter' => (clone $base)->where('created_at', '>=', now()->subWeeks(12)->startOfWeek()),
            'alltime' => clone $base,
            default => (clone $base)->where('created_at', '>=', now()->subDays(29)->startOfDay()),
        };

        $cohortCount = (clone $cohort)->count();
        $verifiedCount = (clone $cohort)->whereNotNull('email_verified_at')->count();
        $rate = $cohortCount > 0 ? (int) round($verifiedCount / $cohortCount * 100) : 0;

        return new MetricValue(
            current: $rate,
            unit: '%',
            lowData: $cohortCount > 0 && $cohortCount < 5,
        );
    }

    public function computeAsOf(CarbonImmutable $date): MetricValue
    {
        $base = $this->signupCohortQuery()
            ->where('created_at', '>=', $date->subDays(29)->startOfDay())
            ->where('created_at', '<=', $date);

        $cohortCount = (clone $base)->count();
        $verifiedCount = (clone $base)
            ->whereNotNull('email_verified_at')
            ->where('email_verified_at', '<=', $date)
            ->count();

        $rate = $cohortCount > 0 ? (int) round($verifiedCount / $cohortCount * 100) : 0;

        return new MetricValue(
            current: $rate,
            unit: '%',
            lowData: $cohortCount > 0 && $cohortCount < 5,
        );
    }

    /** @return Builder<User> */
    private function signupCohortQuery(): Builder
    {
        return User::query()
            ->where('role', '!=', 'admin')
            ->where('email', 'NOT LIKE', '%@import.hartverwarmers.be');
    }
}
