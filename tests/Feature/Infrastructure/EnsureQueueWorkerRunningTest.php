<?php

namespace Tests\Feature\Infrastructure;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EnsureQueueWorkerRunningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // phpunit.xml sets APP_ENV=testing and QUEUE_CONNECTION=sync.
        // Override both so the middleware activates during tests.
        app()->detectEnvironment(fn () => 'local');
        config(['queue.default' => 'database']);
    }

    public function test_middleware_skips_in_non_local_environment(): void
    {
        app()->detectEnvironment(fn () => 'production');
        $user = User::factory()->admin()->create();
        Cache::forget('queue:heartbeat');

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertDontSee('queue-badge');
    }

    public function test_middleware_skips_for_guests(): void
    {
        Cache::forget('queue:heartbeat');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('queue-badge');
    }

    public function test_middleware_skips_for_non_admin_users(): void
    {
        $user = User::factory()->create();
        Cache::forget('queue:heartbeat');

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertDontSee('queue-badge');
    }

    public function test_middleware_skips_when_queue_driver_is_sync(): void
    {
        config(['queue.default' => 'sync']);
        $user = User::factory()->admin()->create();
        Cache::forget('queue:heartbeat');

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertDontSee('queue-badge');
    }

    public function test_healthy_badge_when_heartbeat_is_fresh(): void
    {
        $user = User::factory()->admin()->create();
        Cache::put('queue:heartbeat', now()->timestamp, 60);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('Queue ok');
    }

    public function test_starting_badge_when_heartbeat_is_stale_and_no_cooldown(): void
    {
        $user = User::factory()->admin()->create();
        Cache::forget('queue:heartbeat');
        Cache::forget('queue:autostart-attempted');

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('Starting queue worker');
    }

    public function test_failed_badge_when_heartbeat_is_stale_and_cooldown_active(): void
    {
        $user = User::factory()->admin()->create();
        Cache::forget('queue:heartbeat');
        Cache::put('queue:autostart-attempted', true, 60);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('Queue down');
    }

    public function test_cooldown_prevents_multiple_spawn_attempts(): void
    {
        $user = User::factory()->admin()->create();
        Cache::forget('queue:heartbeat');
        Cache::forget('queue:autostart-attempted');
        // Set a dead PID so the middleware can't find a live worker
        Cache::put('queue:worker-pid', 99999, 3700);

        // First request: no heartbeat, no live worker, no cooldown → starting + sets cooldown
        $this->actingAs($user)->get('/');
        $this->assertTrue(Cache::has('queue:autostart-attempted'));

        // Second request: still no heartbeat, worker still dead, cooldown active → failed
        Cache::put('queue:worker-pid', 99999, 3700);
        $response = $this->actingAs($user)->get('/');
        $response->assertSee('Queue down');
    }

    public function test_fallback_detects_old_unprocessed_jobs(): void
    {
        $user = User::factory()->admin()->create();
        Cache::forget('queue:heartbeat');
        Cache::forget('queue:autostart-attempted');

        // Insert a stale job into the jobs table
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'TestJob']),
            'attempts' => 0,
            'available_at' => now()->subSeconds(60)->timestamp,
            'created_at' => now()->subSeconds(60)->timestamp,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        // Should show starting because there are stale unprocessed jobs
        $response->assertSee('Starting queue worker');
    }
}
