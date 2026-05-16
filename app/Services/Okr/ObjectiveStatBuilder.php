<?php

namespace App\Services\Okr;

use App\Models\Okr\Objective;
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
        $primaryKr = $objective->keyResults->first();
        $metricKey = $primaryKr?->metric_key;

        if ($metricKey === null) {
            return null;
        }

        return new ObjectiveStat(
            title: $objective->title,
            slug: $objective->slug,
            value: $this->registry->compute($metricKey, $range),
            target: $primaryKr->target_value,
            metricKey: $metricKey,
        );
    }
}
