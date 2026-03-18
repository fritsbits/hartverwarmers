<?php

namespace Tests\Feature;

use App\Jobs\QueueHeartbeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueHeartbeatTest extends TestCase
{
    use RefreshDatabase;

    public function test_heartbeat_job_writes_cache_entry(): void
    {
        Cache::forget('queue-heartbeat');

        (new QueueHeartbeat)->handle();

        $this->assertNotNull(Cache::get('queue-heartbeat'));
    }

    public function test_command_dispatches_heartbeat_job(): void
    {
        Queue::fake();
        Cache::put('queue-heartbeat', now()->timestamp, 600);

        $this->artisan('queue:heartbeat')
            ->assertSuccessful();

        Queue::assertPushed(QueueHeartbeat::class);
    }

    public function test_command_reports_ok_when_heartbeat_is_recent(): void
    {
        Queue::fake();
        Cache::put('queue-heartbeat', now()->timestamp, 600);

        $this->artisan('queue:heartbeat')
            ->expectsOutputToContain('OK')
            ->assertSuccessful();
    }

    public function test_command_alerts_when_heartbeat_is_missing(): void
    {
        Queue::fake();
        Mail::fake();
        Cache::forget('queue-heartbeat');
        Cache::forget('queue-heartbeat:alerted');

        $this->artisan('queue:heartbeat')
            ->expectsOutputToContain('missed')
            ->assertSuccessful();

        $this->assertTrue(Cache::get('queue-heartbeat:alerted'));
    }

    public function test_command_does_not_send_duplicate_alerts(): void
    {
        Queue::fake();
        Mail::fake();
        Cache::forget('queue-heartbeat');
        Cache::put('queue-heartbeat:alerted', true, 3600);

        $this->artisan('queue:heartbeat')
            ->assertSuccessful();

        Mail::assertNothingQueued();
    }

    public function test_command_clears_alert_flag_when_recovered(): void
    {
        Queue::fake();
        Cache::put('queue-heartbeat', now()->timestamp, 600);
        Cache::put('queue-heartbeat:alerted', true, 3600);

        $this->artisan('queue:heartbeat')
            ->assertSuccessful();

        $this->assertNull(Cache::get('queue-heartbeat:alerted'));
    }

    public function test_command_alerts_when_heartbeat_is_stale(): void
    {
        Queue::fake();
        Mail::fake();
        Cache::put('queue-heartbeat', now()->subMinutes(15)->timestamp, 600);
        Cache::forget('queue-heartbeat:alerted');

        $this->artisan('queue:heartbeat')
            ->expectsOutputToContain('missed')
            ->assertSuccessful();

        $this->assertTrue(Cache::get('queue-heartbeat:alerted'));
    }
}
