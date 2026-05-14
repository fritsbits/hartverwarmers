<?php

use App\Metrics\OnboardingSignupCountMetric;
use App\Metrics\OnboardingVerificationRateMetric;
use App\Metrics\PresentationScoreAvgMetric;

/*
 * Map of metric_key (string used by KeyResult.metric_key) to a Metric-class FQN.
 * Computation logic lives in code; targets and labels live in the okr_key_results table.
 */

return [
    'presentation_score_avg' => PresentationScoreAvgMetric::class,
    'onboarding_signup_count' => OnboardingSignupCountMetric::class,
    'onboarding_verification_rate' => OnboardingVerificationRateMetric::class,
];
