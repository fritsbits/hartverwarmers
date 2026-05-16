<?php

namespace App\Services\Okr;

final class ObjectiveStat
{
    public function __construct(
        public readonly string $title,
        public readonly string $slug,
        public readonly MetricValue $value,
        public readonly ?int $target = null,
        public readonly ?string $metricKey = null,
    ) {}
}
