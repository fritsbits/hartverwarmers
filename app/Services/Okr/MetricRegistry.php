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
     * compute() and the in-progress-week computeAsOf are display-only — no
     * write/decision path reads them (BaselineCapturer uses
     * computeAsOf($started_at), a fixed date). Freshness is irrelevant for
     * the admin-only OKR dashboard; the hourly okr:warm-metrics command
     * re-primes well inside this window.
     */
    private const COMPUTE_TTL_HOURS = 12;

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
        return $this->memo['c|'.$key.'|'.$range] ??= Cache::remember(
            'okr.compute.'.$key.'.'.$range,
            now()->addHours(self::COMPUTE_TTL_HOURS),
            fn () => $this->resolve($key)->compute($range),
        );
    }

    public function computeAsOf(string $key, CarbonImmutable $date): MetricValue
    {
        return $this->memo['a|'.$key.'|'.$date->toIso8601String()] ??= $this->cachedAsOf($key, $date);
    }

    private function cachedAsOf(string $key, CarbonImmutable $date): MetricValue
    {
        // A fully-elapsed past week is immutable → long TTL. The in-progress
        // week still accumulates, but freshness is irrelevant here and the
        // warm command re-primes hourly → cache it with the short TTL too.
        $ttl = $date->isPast()
            ? now()->addDays(self::HISTORICAL_TTL_DAYS)
            : now()->addHours(self::COMPUTE_TTL_HOURS);

        return Cache::remember(
            'okr.asof.'.$key.'.'.$date->format('Y-m-d'),
            $ttl,
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
