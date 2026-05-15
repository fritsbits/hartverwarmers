<?php

namespace App\Metrics;

use App\Models\OnboardingEmailLog;
use App\Services\Okr\Metric;
use App\Services\Okr\MetricValue;
use BadMethodCallException;
use Carbon\CarbonImmutable;

class NewsletterActivationRateMetric implements Metric
{
    public function compute(string $range): MetricValue
    {
        $cutoff = match ($range) {
            'week' => now()->subDays(7),
            'quarter' => now()->subDays(90),
            'alltime' => null,
            default => now()->subDays(30),
        };

        $sends = OnboardingEmailLog::query()
            ->where('mail_key', 'LIKE', 'newsletter-cycle-%')
            ->whereHas('user', fn ($q) => $q
                ->where('role', '!=', 'admin')
                ->where('email', 'NOT LIKE', '%@import.hartverwarmers.be')
            )
            ->when($cutoff !== null, fn ($q) => $q->where('sent_at', '>=', $cutoff))
            ->with('user:id,last_visited_at')
            ->get(['user_id', 'sent_at']);

        $sent = $sends->count();
        $activated = 0;

        foreach ($sends as $send) {
            $lastVisited = $send->user?->last_visited_at;
            if (! $lastVisited) {
                continue;
            }

            if ($lastVisited->greaterThanOrEqualTo($send->sent_at)
                && $lastVisited->lessThanOrEqualTo($send->sent_at->copy()->addDays(7))
            ) {
                $activated++;
            }
        }

        $rate = $sent > 0 ? (int) round($activated / $sent * 100) : 0;

        return new MetricValue(
            current: $rate,
            previous: null,
            unit: '%',
            lowData: $sent > 0 && $sent < 5,
        );
    }

    public function computeAsOf(CarbonImmutable $date): MetricValue
    {
        throw new BadMethodCallException(static::class.'::computeAsOf not yet implemented.');
    }
}
