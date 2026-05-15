<?php

namespace Tests\Feature\Okr;

use App\Models\Okr\KeyResult;
use App\Services\Okr\Metric;
use App\Services\Okr\MetricRegistry;
use App\Services\Okr\MetricValue;
use Carbon\CarbonImmutable;
use Database\Seeders\OkrSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class MetricRegistryTest extends TestCase
{
    use RefreshDatabase;

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
