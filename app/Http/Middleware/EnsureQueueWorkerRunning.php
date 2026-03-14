<?php

namespace App\Http\Middleware;

use App\Jobs\QueueHealthCheck;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureQueueWorkerRunning
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldCheck($request)) {
            return $next($request);
        }

        $status = $this->determineStatus();
        View::share('queueStatus', $status);

        return $next($request);
    }

    private function shouldCheck(Request $request): bool
    {
        if (! app()->isLocal()) {
            return false;
        }

        if (config('queue.default') === 'sync') {
            return false;
        }

        if (! $request->user()?->isAdmin()) {
            return false;
        }

        if ($request->expectsJson() || $request->header('X-Livewire')) {
            return false;
        }

        return true;
    }

    private function determineStatus(): string
    {
        $heartbeat = Cache::get('queue:heartbeat');

        if ($heartbeat && $heartbeat > now()->subSeconds(30)->timestamp) {
            return 'healthy';
        }

        if (Cache::has('queue:autostart-attempted')) {
            return 'failed';
        }

        $this->attemptAutoStart();

        return 'starting';
    }

    private function attemptAutoStart(): void
    {
        Cache::put('queue:autostart-attempted', true, 60);

        QueueHealthCheck::dispatch();

        $this->spawnWorker();
    }

    private function spawnWorker(): void
    {
        $pid = Cache::get('queue:worker-pid');
        if ($pid && $this->isProcessAlive($pid)) {
            return;
        }

        $artisan = base_path('artisan');
        $php = PHP_BINARY;

        $output = [];
        exec("{$php} {$artisan} queue:listen --tries=1 --max-time=3600 > /dev/null 2>&1 & echo $!", $output);

        if (! empty($output[0]) && is_numeric($output[0])) {
            Cache::put('queue:worker-pid', (int) $output[0], 3700);
        }
    }

    private function isProcessAlive(int $pid): bool
    {
        if (! function_exists('posix_getpgid')) {
            return false;
        }

        return posix_getpgid($pid) !== false;
    }
}
