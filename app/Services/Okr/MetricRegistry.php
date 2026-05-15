<?php

namespace App\Services\Okr;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class MetricRegistry
{
    /**
     * Past, fully-elapsed cutoffs are immutable: content rows are only ever
     * inserted with an increasing created_at, so an aggregate filtered to
     * "<= a past date" never changes. A long TTL is just garbage collection.
     */
    private const HISTORICAL_TTL_DAYS = 30;

    /**
     * Request-scoped memo. The registry is bound as a singleton, so a given
     * (key, range) / (key, date) is computed at most once per request. This
     * collapses the duplication where multiple initiatives sharing an
     * objective each recompute the same metric weeks for their sparklines.
     *
     * @var array<string, MetricValue>
     */
    private array $memo = [];

    /**
     * @param  array<string, class-string<Metric>>  $metrics  metric_key => Metric-class FQN
     */
    public function __construct(private readonly array $metrics) {}

    public function compute(string $key, string $range): MetricValue
    {
        return $this->memo['c|'.$key.'|'.$range] ??= $this->resolve($key)->compute($range);
    }

    public function computeAsOf(string $key, CarbonImmutable $date): MetricValue
    {
        return $this->memo['a|'.$key.'|'.$date->toIso8601String()] ??= $this->cachedAsOf($key, $date);
    }

    private function cachedAsOf(string $key, CarbonImmutable $date): MetricValue
    {
        // The in-progress week is still accumulating data — only persist
        // cutoffs whose week has fully ended (the sparkline's dominant cost,
        // and the part that otherwise grows unbounded as initiatives age).
        if (! $date->isPast()) {
            return $this->resolve($key)->computeAsOf($date);
        }

        return Cache::remember(
            'okr.asof.'.$key.'.'.$date->format('Y-m-d'),
            now()->addDays(self::HISTORICAL_TTL_DAYS),
            fn () => $this->resolve($key)->computeAsOf($date),
        );
    }

    private function resolve(string $key): Metric
    {
        if (! isset($this->metrics[$key])) {
            throw new InvalidArgumentException("Unknown metric: {$key}");
        }

        return app($this->metrics[$key]);
    }
}
