<?php

namespace Tests\Feature\Okr;

use App\Models\Okr\KeyResult;
use App\Services\Okr\Metric;
use App\Services\Okr\MetricRegistry;
use App\Services\Okr\MetricValue;
use Carbon\CarbonImmutable;
use Database\Seeders\OkrSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Tests\TestCase;

class MetricRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // array cache driver in tests → flush is process-isolated and safe
        Cache::flush();
        CountingMetric::$calls = 0;
    }

    public function test_compute_dispatches_to_registered_class(): void
    {
        $registry = new MetricRegistry(['fake_metric' => FakeMetric::class]);

        $value = $registry->compute('fake_metric', 'month');

        $this->assertSame(42, $value->current);
        $this->assertSame('%', $value->unit);
    }

    public function test_unknown_key_throws(): void
    {
        $registry = new MetricRegistry([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown metric: missing_key');

        $registry->compute('missing_key', 'month');
    }

    public function test_every_seeded_metric_key_resolves_to_a_class(): void
    {
        $this->seed(OkrSeeder::class);

        /** @var MetricRegistry $registry */
        $registry = app(MetricRegistry::class);

        $keys = KeyResult::whereNotNull('metric_key')->pluck('metric_key');

        foreach ($keys as $key) {
            try {
                $value = $registry->compute($key, 'month');
                $this->assertInstanceOf(MetricValue::class, $value);
            } catch (InvalidArgumentException $e) {
                $this->fail("Seeded metric_key '{$key}' is not registered in config/okr-metrics.php");
            }
        }
    }

    public function test_compute_is_memoized_within_a_request(): void
    {
        CountingMetric::$calls = 0;
        $registry = new MetricRegistry(['counter' => CountingMetric::class]);

        $registry->compute('counter', 'month');
        $registry->compute('counter', 'month');

        $this->assertSame(1, CountingMetric::$calls);
    }

    public function test_historical_as_of_is_cached_across_instances(): void
    {
        CountingMetric::$calls = 0;
        $past = CarbonImmutable::now()->subWeeks(2)->endOfWeek();

        (new MetricRegistry(['counter' => CountingMetric::class]))->computeAsOf('counter', $past);
        (new MetricRegistry(['counter' => CountingMetric::class]))->computeAsOf('counter', $past);

        $this->assertSame(1, CountingMetric::$calls, 'A fully-elapsed week is immutable and must only be computed once.');
    }

    public function test_in_progress_week_is_cached_with_short_ttl(): void
    {
        CountingMetric::$calls = 0;
        $future = CarbonImmutable::now()->addWeek()->endOfWeek();

        (new MetricRegistry(['counter' => CountingMetric::class]))->computeAsOf('counter', $future);
        (new MetricRegistry(['counter' => CountingMetric::class]))->computeAsOf('counter', $future);

        $this->assertSame(1, CountingMetric::$calls, 'An in-progress week is cached with a short TTL; the warm command re-primes hourly.');
    }

    public function test_compute_is_cached_across_instances(): void
    {
        CountingMetric::$calls = 0;

        (new MetricRegistry(['counter' => CountingMetric::class]))->compute('counter', 'month');
        (new MetricRegistry(['counter' => CountingMetric::class]))->compute('counter', 'month');

        $this->assertSame(1, CountingMetric::$calls, 'compute() must be cached across requests, not only memoized per instance.');
    }

    public function test_current_week_as_of_is_cached_across_instances(): void
    {
        CountingMetric::$calls = 0;
        // A non-past date exercises the previously-live cachedAsOf() branch.
        $notPast = CarbonImmutable::now()->addDay();

        (new MetricRegistry(['counter' => CountingMetric::class]))->computeAsOf('counter', $notPast);
        (new MetricRegistry(['counter' => CountingMetric::class]))->computeAsOf('counter', $notPast);

        $this->assertSame(1, CountingMetric::$calls, 'The in-progress / non-past week must now be cached too.');
    }
}

class FakeMetric implements Metric
{
    public function compute(string $range): MetricValue
    {
        return new MetricValue(current: 42, unit: '%');
    }

    public function computeAsOf(CarbonImmutable $date): MetricValue
    {
        return new MetricValue(current: 42, unit: '%');
    }
}

class CountingMetric implements Metric
{
    public static int $calls = 0;

    public function compute(string $range): MetricValue
    {
        self::$calls++;

        return new MetricValue(current: 1, unit: '%');
    }

    public function computeAsOf(CarbonImmutable $date): MetricValue
    {
        self::$calls++;

        return new MetricValue(current: 1, unit: '%');
    }
}
