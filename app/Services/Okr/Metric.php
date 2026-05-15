<?php

namespace App\Services\Okr;

use Carbon\CarbonImmutable;

interface Metric
{
    public function compute(string $range): MetricValue;

    public function computeAsOf(CarbonImmutable $date): MetricValue;
}
