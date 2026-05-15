<?php

namespace App\Services\Okr;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class MetricRegistry
{
    /**
     * @param  array<string, class-string<Metric>>  $metrics  metric_key => Metric-class FQN
     */
    public function __construct(private readonly array $metrics) {}

    public function compute(string $key, string $range): MetricValue
    {
        if (! isset($this->metrics[$key])) {
            throw new InvalidArgumentException("Unknown metric: {$key}");
        }

        return app($this->metrics[$key])->compute($range);
    }

    public function computeAsOf(string $key, CarbonImmutable $date): MetricValue
    {
        if (! isset($this->metrics[$key])) {
            throw new InvalidArgumentException("Unknown metric: {$key}");
        }

        return app($this->metrics[$key])->computeAsOf($date);
    }
}
