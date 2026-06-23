<?php

namespace App\Services\Okr;

final class MetricValue
{
    public function __construct(
        public readonly int|float|null $current = null,
        public readonly int|float|null $previous = null,
        public readonly string $unit = '',
        public readonly bool $lowData = false,
        public readonly ?int $outOf = null,
    ) {}

    public function delta(): int|float|null
    {
        if ($this->current === null || $this->previous === null) {
            return null;
        }

        return $this->current - $this->previous;
    }

    public function display(): string
    {
        if ($this->current === null) {
            return '—';
        }

        if ($this->outOf !== null) {
            return "{$this->current}/{$this->outOf}";
        }

        return "{$this->current}{$this->unit}";
    }
}
