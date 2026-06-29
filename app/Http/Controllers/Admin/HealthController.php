<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ServerHealth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class HealthController extends Controller
{
    public function index(): View
    {
        return view('admin.health', [
            'memory' => ServerHealth::memory(),
            'disk' => ServerHealth::disk(),
            'load' => ServerHealth::loadAverage(),
            'queue' => ServerHealth::queueHealth(),
            'errors' => ServerHealth::recentErrors(),
            'failedSummary' => ServerHealth::failedJobsSummary(),
            'latestFailed' => ServerHealth::latestFailedJob(),
            'uptime' => trim((string) @shell_exec('uptime -p 2>/dev/null') ?: ''),
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => app()->version(),
        ]);
    }

    public function flushFailedJobs(): RedirectResponse
    {
        Artisan::call('queue:flush');

        return back()->with('status', 'Alle mislukte taken zijn gewist.');
    }
}
