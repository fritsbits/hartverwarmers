<?php

namespace App\Services\Okr;

use App\Models\Okr\Initiative;
use App\Models\Okr\KeyResult;
use Carbon\CarbonImmutable;

class InitiativeImpact
{
    public function __construct(private readonly MetricRegistry $registry) {}

    public function forInitiative(Initiative $initiative): InitiativeImpactSummary
    {
        $initiative->loadMissing(['objective.keyResults', 'baselines']);

        $krImpacts = $initiative->objective->keyResults->map(
            fn (KeyResult $kr) => $this->buildKrImpact($initiative, $kr),
        );

        return new InitiativeImpactSummary($initiative, $krImpacts);
    }

    private function buildKrImpact(Initiative $initiative, KeyResult $kr): InitiativeKrImpact
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

        $sparkline = $this->sparkline($kr->metric_key, $initiative->started_at);
        $markerIndex = $this->markerIndex($sparkline);

        return new InitiativeKrImpact(
            krId: $kr->id,
            krLabel: $kr->label,
            baselineValue: $baselineValue,
            currentValue: $currentValue,
            delta: $delta,
            unit: $current->unit ?: ($baseline?->baseline_unit ?? ''),
            baselineLowData: (bool) $baseline?->low_data,
            currentLowData: $current->lowData,
            sparkline: $sparkline,
            markerIndex: $markerIndex,
        );
    }

    /**
     * @return array<int, array{label: string, value: int|float|null}>
     */
    private function sparkline(?string $metricKey, ?\DateTimeInterface $startedAt): array
    {
        if ($metricKey === null || $startedAt === null) {
            return [];
        }

        $start = CarbonImmutable::instance($startedAt)->subWeeks(4)->startOfWeek();
        $end = CarbonImmutable::now()->endOfWeek();
        $cursor = $start;
        $points = [];

        while ($cursor <= $end) {
            $value = $this->registry->computeAsOf($metricKey, $cursor->endOfWeek());
            $points[] = [
                'label' => $cursor->format('d M'),
                'value' => $value->current,
            ];
            $cursor = $cursor->addWeek();
        }

        return $points;
    }

    /**
     * @param  array<int, array{label: string, value: int|float|null}>  $sparkline
     */
    private function markerIndex(array $sparkline): int
    {
        if (empty($sparkline)) {
            return 0;
        }

        // Sparkline starts 4 weeks before started_at, so the start bucket is index 4.
        // Clamp to the last index if the sparkline is shorter (e.g. very recent started_at).
        return min(4, count($sparkline) - 1);
    }
}
