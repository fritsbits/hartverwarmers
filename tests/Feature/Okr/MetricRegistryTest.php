<?php

namespace Tests\Feature\Okr;

use App\Services\Okr\Metric;
use App\Services\Okr\MetricRegistry;
use App\Services\Okr\MetricValue;
use InvalidArgumentException;
use Tests\TestCase;

class MetricRegistryTest extends TestCase
{
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
}

class FakeMetric implements Metric
{
    public function compute(string $range): MetricValue
    {
        return new MetricValue(current: 42, unit: '%');
    }
}
