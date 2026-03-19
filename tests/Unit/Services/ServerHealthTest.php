<?php

namespace Tests\Unit\Services;

use App\Services\ServerHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ServerHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_disk_returns_used_total_and_percent(): void
    {
        $result = ServerHealth::disk();

        $this->assertArrayHasKey('used', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('percent', $result);
        $this->assertIsInt($result['used']);
        $this->assertIsInt($result['total']);
        $this->assertIsFloat($result['percent']);
        $this->assertGreaterThan(0, $result['total']);
        $this->assertGreaterThanOrEqual(0, $result['percent']);
        $this->assertLessThanOrEqual(100, $result['percent']);
    }

    public function test_load_average_returns_three_values(): void
    {
        $result = ServerHealth::loadAverage();

        if ($result === null) {
            $this->markTestSkipped('sys_getloadavg() not available on this platform');
        }

        $this->assertArrayHasKey('1m', $result);
        $this->assertArrayHasKey('5m', $result);
        $this->assertArrayHasKey('15m', $result);
        $this->assertIsFloat($result['1m']);
    }

    public function test_memory_returns_array_or_null(): void
    {
        $result = ServerHealth::memory();

        if ($result === null) {
            // macOS or unsupported platform — acceptable
            $this->assertTrue(true);

            return;
        }

        $this->assertArrayHasKey('used', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('percent', $result);
        $this->assertGreaterThan(0, $result['total']);
    }

    public function test_queue_health_returns_expected_keys(): void
    {
        $result = ServerHealth::queueHealth();

        $this->assertArrayHasKey('heartbeat_age', $result);
        $this->assertArrayHasKey('pending', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertIsInt($result['pending']);
        $this->assertIsInt($result['failed']);
    }

    public function test_queue_health_reads_heartbeat_from_cache(): void
    {
        Cache::put('queue-heartbeat', now()->timestamp, 600);

        $result = ServerHealth::queueHealth();

        $this->assertNotNull($result['heartbeat_age']);
        $this->assertLessThanOrEqual(5, $result['heartbeat_age']);
    }

    public function test_recent_errors_returns_collection(): void
    {
        $result = ServerHealth::recentErrors(5);

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_status_for_value_returns_correct_status(): void
    {
        $this->assertEquals('green', ServerHealth::statusForValue(50, 'memory_percent'));
        $this->assertEquals('amber', ServerHealth::statusForValue(75, 'memory_percent'));
        $this->assertEquals('red', ServerHealth::statusForValue(90, 'memory_percent'));
    }
}
