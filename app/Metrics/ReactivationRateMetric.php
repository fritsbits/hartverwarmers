<?php

namespace App\Metrics;

use App\Models\OnboardingEmailLog;
use App\Services\Okr\Metric;
use App\Services\Okr\MetricPeriod;
use App\Services\Okr\MetricValue;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ReactivationRateMetric implements Metric
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
            ->where('mail_key', config('newsletter.reactivation_mail_key'))
            ->when($cutoff !== null, fn ($q) => $q->where('sent_at', '>=', $cutoff))
            ->with('user:id,last_visited_at')
            ->get(['user_id', 'sent_at']);

        return $this->rate(
            $sends,
            fn ($lastVisited, $send): bool => $lastVisited->greaterThanOrEqualTo($send->sent_at),
        );
    }

    public function caption(string $range): string
    {
        return 'van slapers actief na reactivatiemail · '.MetricPeriod::label($range);
    }

    public function computeAsOf(CarbonImmutable $date): MetricValue
    {
        $sends = OnboardingEmailLog::query()
            ->where('mail_key', config('newsletter.reactivation_mail_key'))
            ->where('sent_at', '<=', $date)
            ->with('user:id,last_visited_at')
            ->get(['user_id', 'sent_at']);

        return $this->rate(
            $sends,
            fn ($lastVisited, $send): bool => $lastVisited->greaterThanOrEqualTo($send->sent_at)
                && $lastVisited->lessThanOrEqualTo($date),
        );
    }

    /**
     * @param  Collection<int, OnboardingEmailLog>  $sends
     */
    private function rate(Collection $sends, callable $isReactivated): MetricValue
    {
        $sent = $sends->count();
        $reactivated = 0;

        foreach ($sends as $send) {
            $lastVisited = $send->user?->last_visited_at;
            if (! $lastVisited) {
                continue;
            }

            if ($isReactivated($lastVisited, $send)) {
                $reactivated++;
            }
        }

        $rate = $sent > 0 ? (int) round($reactivated / $sent * 100) : 0;

        return new MetricValue(
            current: $rate,
            previous: null,
            unit: '%',
            lowData: $sent > 0 && $sent < 50,
        );
    }
}
