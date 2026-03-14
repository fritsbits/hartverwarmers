<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class QueueHealthCheck implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Cache::put('queue:heartbeat', now()->timestamp, 60);
    }
}
