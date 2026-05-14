<?php

use App\Metrics\PresentationScoreAvgMetric;

/*
 * Map of metric_key (string used by KeyResult.metric_key) to a Metric-class FQN.
 * Computation logic lives in code; targets and labels live in the okr_key_results table.
 */

return [
    'presentation_score_avg' => PresentationScoreAvgMetric::class,
];
