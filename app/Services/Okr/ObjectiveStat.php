<?php

namespace App\Services\Okr;

final class ObjectiveStat
{
    /**
     * @param  array<int, array{label: string, value: int|float}>  $series
     */
    public function __construct(
        public readonly string $title,
        public readonly string $slug,
        public readonly MetricValue $value,
        public readonly array $series = [],
    ) {}
}
