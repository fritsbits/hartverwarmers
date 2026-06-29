<?php

namespace App\Metrics;

use App\Models\OnboardingEmailLog;
use App\Services\Okr\Metric;
use App\Services\Okr\MetricPeriod;
use App\Services\Okr\MetricValue;
use App\Services\Okr\ProvidesActivityBreakdown;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ReactivationRateMetric implements Metric, ProvidesActivityBreakdown
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

    public function activityByDay(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $sends = OnboardingEmailLog::query()
            ->where('mail_key', config('newsletter.reactivation_mail_key'))
            ->whereBetween('sent_at', [$from, $to])
            ->with('user:id,last_visited_at')
            ->get(['user_id', 'sent_at']);

        $byDay = [];
        foreach ($sends as $send) {
            $key = $send->sent_at->format('Y-m-d');
            $byDay[$key]['effort'] = ($byDay[$key]['effort'] ?? 0) + 1;

            $lastVisited = $send->user?->last_visited_at;
            $returned = $lastVisited && $lastVisited->greaterThanOrEqualTo($send->sent_at);
            $byDay[$key]['result'] = ($byDay[$key]['result'] ?? 0) + ($returned ? 1 : 0);
        }

        $rows = [];
        $cursor = $from->startOfDay();
        $end = $to->startOfDay();
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m-d');
            $rows[] = [
                'label' => $cursor->format('d M'),
                'effort' => $byDay[$key]['effort'] ?? 0,
                'result' => $byDay[$key]['result'] ?? 0,
            ];
            $cursor = $cursor->addDay();
        }

        return $rows;
    }

    public function effortLabel(): string
    {
        return 'verstuurde mails';
    }

    public function resultLabel(): string
    {
        return 'opnieuw actief';
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
