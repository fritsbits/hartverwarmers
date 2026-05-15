<?php

namespace App\Services\Okr;

use App\Models\Okr\Objective;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class ObjectiveStatBuilder
{
    public function __construct(private readonly MetricRegistry $registry) {}

    /**
     * @param  Collection<int, Objective>  $objectives
     * @return Collection<int, ObjectiveStat>
     */
    public function build(Collection $objectives, string $range): Collection
    {
        return $objectives
            ->map(fn (Objective $objective) => $this->forObjective($objective, $range))
            ->filter()
            ->values();
    }

    /**
     * The headline metric is the objective's first key result by position
     * (the `keyResults` relation is ordered by `position`). Objectives whose
     * primary KR has no `metric_key` get no card.
     */
    private function forObjective(Objective $objective, string $range): ?ObjectiveStat
    {
        $metricKey = $objective->keyResults->first()?->metric_key;

        if ($metricKey === null) {
            return null;
        }

        return new ObjectiveStat(
            title: $objective->title,
            slug: $objective->slug,
            value: $this->registry->compute($metricKey, $range),
            series: $this->series($metricKey, $range),
        );
    }

    /**
     * @return array<int, array{label: string, value: int|float}>
     */
    private function series(string $metricKey, string $range): array
    {
        [$count, $unit] = match ($range) {
            'week' => [8, 'day'],
            'quarter' => [12, 'week'],
            'alltime' => [12, 'month'],
            default => [8, 'week'], // 'month' (the default range): 8 weekly points
        };

        $now = CarbonImmutable::now();
        $points = [];

        for ($i = $count - 1; $i >= 0; $i--) {
            $base = match ($unit) {
                'day' => $now->subDays($i),
                'week' => $now->subWeeks($i),
                'month' => $now->subMonths($i),
            };
            $date = $base->endOfDay();

            $value = $this->registry->computeAsOf($metricKey, $date)->current;

            if ($value === null) {
                continue;
            }

            $points[] = ['label' => $date->format('Y-m-d'), 'value' => $value];
        }

        return $points;
    }
}
