<?php

namespace App\Services\Okr;

use App\Models\Okr\Initiative;
use App\Models\Okr\InitiativeBaseline;
use Carbon\CarbonImmutable;

class BaselineCapturer
{
    public function __construct(private readonly MetricRegistry $registry) {}

    public function captureFor(Initiative $initiative): void
    {
        if ($initiative->started_at === null) {
            return;
        }

        $existingKrIds = InitiativeBaseline::query()
            ->where('initiative_id', $initiative->id)
            ->pluck('key_result_id')
            ->all();

        $krs = $initiative->objective->keyResults()->get();
        $asOf = CarbonImmutable::instance($initiative->started_at);
        $now = now();

        foreach ($krs as $kr) {
            if (in_array($kr->id, $existingKrIds, true)) {
                continue;
            }

            if ($kr->metric_key === null) {
                InitiativeBaseline::create([
                    'initiative_id' => $initiative->id,
                    'key_result_id' => $kr->id,
                    'baseline_value' => null,
                    'baseline_unit' => $kr->target_unit ?? '',
                    'baseline_at' => $now,
                    'low_data' => false,
                ]);

                continue;
            }

            $value = $this->registry->computeAsOf($kr->metric_key, $asOf);

            InitiativeBaseline::create([
                'initiative_id' => $initiative->id,
                'key_result_id' => $kr->id,
                'baseline_value' => $value->current,
                'baseline_unit' => $value->unit,
                'baseline_at' => $now,
                'low_data' => $value->lowData,
            ]);
        }
    }
}
