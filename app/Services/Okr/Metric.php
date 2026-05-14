<?php

namespace App\Services\Okr;

interface Metric
{
    public function compute(string $range): MetricValue;
}
