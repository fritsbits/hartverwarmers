<?php

namespace App\Metrics;

use App\Models\User;
use App\Services\Okr\Metric;
use App\Services\Okr\MetricValue;
use BadMethodCallException;
use Carbon\CarbonImmutable;

class OnboardingReturn7dRateMetric implements Metric
{
    public function compute(string $range): MetricValue
    {
        $cohortStart = match ($range) {
            'week' => now()->subDays(7),
            'quarter' => now()->subDays(90),
            'alltime' => null,
            default => now()->subDays(30),
        };

        $cohort = User::query()
            ->whereNotNull('email_verified_at')
            ->where('role', '!=', 'admin')
            ->when($cohortStart !== null, fn ($q) => $q->where('created_at', '>=', $cohortStart))
            ->get(['id', 'first_return_at']);

        $total = $cohort->count();
        $returned = $cohort->filter(fn ($u) => $u->getRawOriginal('first_return_at') !== null)->count();
        $rate = $total > 0 ? (int) round($returned / $total * 100) : 0;

        return new MetricValue(current: $rate, unit: '%', lowData: $total > 0 && $total < 5);
    }

    public function computeAsOf(CarbonImmutable $date): MetricValue
    {
        throw new BadMethodCallException(static::class.'::computeAsOf not yet implemented.');
    }
}
