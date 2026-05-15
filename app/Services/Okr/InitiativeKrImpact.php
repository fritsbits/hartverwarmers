<?php

namespace App\Services\Okr;

final class InitiativeKrImpact
{
    /**
     * @param  array<int, array{label: string, value: int|float|null}>  $sparkline
     */
    public function __construct(
        public readonly int $krId,
        public readonly string $krLabel,
        public readonly int|float|null $baselineValue,
        public readonly int|float|null $currentValue,
        public readonly int|float|null $delta,
        public readonly string $unit,
        public readonly bool $baselineLowData,
        public readonly bool $currentLowData,
        public readonly array $sparkline,
        public readonly int $markerIndex,
    ) {}
}
