<?php

namespace Tests\Feature;

use App\Jobs\QueueHealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class QueueHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_writes_heartbeat_to_cache(): void
    {
        Cache::forget('queue:heartbeat');

        (new QueueHealthCheck)->handle();

        $heartbeat = Cache::get('queue:heartbeat');
        $this->assertNotNull($heartbeat);
        $this->assertEqualsWithDelta(now()->timestamp, $heartbeat, 2);
    }

    public function test_heartbeat_has_sixty_second_ttl(): void
    {
        Cache::forget('queue:heartbeat');

        (new QueueHealthCheck)->handle();

        $this->assertTrue(Cache::has('queue:heartbeat'));

        $this->travel(61)->seconds();
        $this->assertFalse(Cache::has('queue:heartbeat'));
    }
}
