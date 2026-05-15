<?php

namespace App\Metrics;

use App\Models\User;
use App\Services\Okr\Metric;
use App\Services\Okr\MetricValue;
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
        $cohort = User::query()
            ->whereNotNull('email_verified_at')
            ->where('email_verified_at', '<=', $date)
            ->where('role', '!=', 'admin')
            ->where('created_at', '>=', $date->subDays(29)->startOfDay())
            ->where('created_at', '<=', $date)
            ->get(['id', 'first_return_at']);

        $total = $cohort->count();
        $returned = $cohort->filter(function ($u) use ($date) {
            $raw = $u->getRawOriginal('first_return_at');

            return $raw !== null && $u->first_return_at <= $date;
        })->count();

        $rate = $total > 0 ? (int) round($returned / $total * 100) : 0;

        return new MetricValue(
            current: $rate,
            unit: '%',
            lowData: $total > 0 && $total < 5,
        );
    }
}
