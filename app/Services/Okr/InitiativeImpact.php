<?php

namespace App\Services\Okr;

use App\Models\Okr\Initiative;
use App\Models\Okr\KeyResult;
use Carbon\CarbonImmutable;

class InitiativeImpact
{
    /**
     * Days of pre-launch context shown before a daily trajectory, so a short
     * experiment still has a baseline week to compare against.
     */
    private const DAILY_LEAD_DAYS = 7;

    /**
     * Weeks of pre-launch context shown before a weekly trajectory.
     */
    private const WEEKLY_LEAD_WEEKS = 4;

    /**
     * Up to this many days live, the trajectory is drawn per day (these
     * reactivation-style experiments run for only a few days and send mail
     * daily). Beyond it, daily bars become an unreadable smear, so it falls
     * back to weekly buckets.
     */
    private const DAILY_MAX_DAYS_LIVE = 21;

    public function __construct(private readonly MetricRegistry $registry) {}

    public function forInitiative(Initiative $initiative): InitiativeImpactSummary
    {
        $initiative->loadMissing(['objective.keyResults', 'baselines']);

        $krImpacts = $initiative->objective->keyResults->map(
            fn (KeyResult $kr) => $this->buildKrImpact($initiative, $kr),
        );

        return new InitiativeImpactSummary($initiative, $krImpacts);
    }

    public function headlineImpact(Initiative $initiative): ?InitiativeKrImpact
    {
        $initiative->loadMissing(['objective.keyResults', 'baselines']);

        $kr = $initiative->objective->keyResults->first();

        if ($kr === null) {
            return null;
        }

        return $this->buildKrImpact($initiative, $kr, withSparkline: false);
    }

    private function buildKrImpact(Initiative $initiative, KeyResult $kr, bool $withSparkline = true): InitiativeKrImpact
    {
        $baseline = $initiative->baselines->firstWhere('key_result_id', $kr->id);
        $current = $kr->metric_key
            ? $this->registry->compute($kr->metric_key, 'alltime')
            : new MetricValue;

        $baselineValue = $baseline?->baseline_value !== null
            ? (float) $baseline->baseline_value
            : null;
        $currentValue = $current->current;

        $delta = null;
        if ($baselineValue !== null && $currentValue !== null) {
            $rawDelta = $currentValue - $baselineValue;
            $delta = (floor($rawDelta) == $rawDelta) ? (int) $rawDelta : round($rawDelta, 2);
        }

        $trajectory = $withSparkline
            ? $this->trajectory($kr->metric_key, $initiative->started_at)
            : ['points' => [], 'markerIndex' => 0, 'periodWord' => 'dag'];

        return new InitiativeKrImpact(
            krId: $kr->id,
            krLabel: $kr->label,
            baselineValue: $baselineValue,
            currentValue: $currentValue,
            delta: $delta,
            unit: $current->unit ?: ($baseline?->baseline_unit ?? ''),
            baselineLowData: (bool) $baseline?->low_data,
            currentLowData: $current->lowData,
            sparkline: $trajectory['points'],
            markerIndex: $trajectory['markerIndex'],
            periodWord: $trajectory['periodWord'],
        );
    }

    /**
     * The metric's trajectory leading up to and since launch. Young initiatives
     * are drawn per day with a week of lead-in; once an initiative has been live
     * for weeks, daily bars are unreadable so it switches to weekly buckets.
     *
     * @return array{points: array<int, array{label: string, value: int|float|null}>, markerIndex: int, periodWord: string}
     */
    private function trajectory(?string $metricKey, ?\DateTimeInterface $startedAt): array
    {
        if ($metricKey === null || $startedAt === null) {
            return ['points' => [], 'markerIndex' => 0, 'periodWord' => 'dag'];
        }

        $started = CarbonImmutable::instance($startedAt);
        $now = CarbonImmutable::now();
        $daysLive = $started->startOfDay()->diffInDays($now->startOfDay());

        if ($daysLive <= self::DAILY_MAX_DAYS_LIVE) {
            return $this->series(
                $metricKey,
                $started->subDays(self::DAILY_LEAD_DAYS)->startOfDay(),
                $now->endOfDay(),
                fn (CarbonImmutable $c): CarbonImmutable => $c->addDay(),
                fn (CarbonImmutable $c): CarbonImmutable => $c->endOfDay(),
                self::DAILY_LEAD_DAYS,
                'dag',
            );
        }

        return $this->series(
            $metricKey,
            $started->subWeeks(self::WEEKLY_LEAD_WEEKS)->startOfWeek(),
            $now->endOfWeek(),
            fn (CarbonImmutable $c): CarbonImmutable => $c->addWeek(),
            fn (CarbonImmutable $c): CarbonImmutable => $c->endOfWeek(),
            self::WEEKLY_LEAD_WEEKS,
            'week',
        );
    }

    /**
     * Walk buckets from $start to $end, sampling the metric as-of each bucket's
     * end. $lead is how many buckets precede launch (so the launch bucket sits
     * at that index, clamped for very recent initiatives).
     *
     * @return array{points: array<int, array{label: string, value: int|float|null}>, markerIndex: int, periodWord: string}
     */
    private function series(
        string $metricKey,
        CarbonImmutable $start,
        CarbonImmutable $end,
        callable $advance,
        callable $bucketEnd,
        int $lead,
        string $periodWord,
    ): array {
        $cursor = $start;
        $points = [];

        while ($cursor <= $end) {
            $value = $this->registry->computeAsOf($metricKey, $bucketEnd($cursor));
            $points[] = [
                'label' => $cursor->format('d M'),
                'value' => $value->current,
            ];
            $cursor = $advance($cursor);
        }

        return [
            'points' => $points,
            'markerIndex' => empty($points) ? 0 : min($lead, count($points) - 1),
            'periodWord' => $periodWord,
        ];
    }
}
