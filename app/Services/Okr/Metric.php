<?php

namespace App\Services\Okr;

use Carbon\CarbonImmutable;

interface Metric
{
    public function compute(string $range): MetricValue;

    public function computeAsOf(CarbonImmutable $date): MetricValue;

    /**
     * A short, self-explanatory description of what the metric counts,
     * scoped to the selected range (e.g. "nieuwe aanmeldingen · laatste 30 dagen").
     */
    public function caption(string $range): string;
}
