<?php

namespace App\Metrics;

use App\Models\User;
use App\Services\Okr\Metric;
use App\Services\Okr\MetricValue;
use BadMethodCallException;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class OnboardingSignupCountMetric implements Metric
{
    public function compute(string $range): MetricValue
    {
        $base = $this->signupCohortQuery();

        if ($range === 'alltime') {
            return new MetricValue(current: (clone $base)->count());
        }

        [$currentStart, $previousStart] = match ($range) {
            'week' => [now()->subDays(6)->startOfDay(), now()->subDays(13)->startOfDay()],
            'quarter' => [now()->subWeeks(12)->startOfWeek(), now()->subWeeks(24)->startOfWeek()],
            default => [now()->subDays(29)->startOfDay(), now()->subDays(59)->startOfDay()],
        };

        $current = (clone $base)->where('created_at', '>=', $currentStart)->count();
        $previous = (clone $base)
            ->where('created_at', '>=', $previousStart)
            ->where('created_at', '<', $currentStart)
            ->count();

        return new MetricValue(current: $current, previous: $previous);
    }

    public function computeAsOf(CarbonImmutable $date): MetricValue
    {
        throw new BadMethodCallException(static::class.'::computeAsOf not yet implemented.');
    }

    /** @return Builder<User> */
    private function signupCohortQuery(): Builder
    {
        return User::query()
            ->where('role', '!=', 'admin')
            ->where('email', 'NOT LIKE', '%@import.hartverwarmers.be');
    }
}
