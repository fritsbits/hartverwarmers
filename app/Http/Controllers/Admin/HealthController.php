<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ServerHealth;
use Illuminate\View\View;

class HealthController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.health', [
            'memory' => ServerHealth::memory(),
            'disk' => ServerHealth::disk(),
            'load' => ServerHealth::loadAverage(),
            'queue' => ServerHealth::queueHealth(),
            'errors' => ServerHealth::recentErrors(),
            'uptime' => trim((string) @shell_exec('uptime -p 2>/dev/null') ?: ''),
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => app()->version(),
        ]);
    }
}
