<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ServerHealth
{
    /**
     * System memory usage from /proc/meminfo (Linux) or null.
     *
     * @return array{used: int, total: int, percent: float}|null
     */
    public static function memory(): ?array
    {
        if (is_readable('/proc/meminfo')) {
            return self::memoryFromProc();
        }

        return null;
    }

    /**
     * Disk usage for the root partition.
     *
     * @return array{used: int, total: int, percent: float}
     */
    public static function disk(): array
    {
        $total = (int) disk_total_space('/');
        $free = (int) disk_free_space('/');
        $used = $total - $free;

        return [
            'used' => $used,
            'total' => $total,
            'percent' => $total > 0 ? round(($used / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * System load average (1m, 5m, 15m) or null if unavailable.
     *
     * @return array{1m: float, 5m: float, 15m: float}|null
     */
    public static function loadAverage(): ?array
    {
        if (! function_exists('sys_getloadavg')) {
            return null;
        }

        $load = sys_getloadavg();

        if ($load === false) {
            return null;
        }

        return [
            '1m' => round($load[0], 2),
            '5m' => round($load[1], 2),
            '15m' => round($load[2], 2),
        ];
    }

    /**
     * Queue health based on heartbeat cache key and job tables.
     *
     * @return array{heartbeat_age: ?int, pending: int, failed: int}
     */
    public static function queueHealth(): array
    {
        $lastBeat = Cache::get('queue-heartbeat');

        return [
            'heartbeat_age' => $lastBeat ? now()->timestamp - $lastBeat : null,
            'pending' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->count(),
        ];
    }

    /**
     * Recent ERROR-level entries from the active log file, grouped by message.
     *
     * @return Collection<int, array{date: string, level: string, message: string, count: int, relative_time: string}>
     */
    public static function recentErrors(int $limit = 10): Collection
    {
        $path = self::currentLogPath();

        if (! $path || ! is_readable($path)) {
            return collect();
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            return collect();
        }

        $fileSize = filesize($path);
        $readBytes = min($fileSize, 50_000);
        fseek($handle, -$readBytes, SEEK_END);
        $content = fread($handle, $readBytes);
        fclose($handle);

        preg_match_all(
            '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \S+\.(ERROR|CRITICAL|ALERT|EMERGENCY): (.+)/m',
            $content,
            $matches,
            PREG_SET_ORDER
        );

        return collect($matches)
            ->reverse()
            ->groupBy(fn ($match) => self::cleanErrorMessage($match[3]))
            ->take($limit)
            ->map(function ($items) {
                $newest = $items->first();

                return [
                    'date' => $newest[1],
                    'level' => $newest[2],
                    'message' => self::cleanErrorMessage($newest[3]),
                    'count' => $items->count(),
                    'relative_time' => self::relativeTime($newest[1]),
                ];
            })
            ->values();
    }

    /**
     * Human-readable Dutch label for server load.
     */
    public static function loadLabel(float $load1m): string
    {
        return match (self::statusForValue($load1m, 'load_average')) {
            'red' => 'Overbelast',
            'amber' => 'Druk',
            default => 'Normaal',
        };
    }

    /**
     * Format a datetime string as relative time in Dutch.
     */
    public static function relativeTime(string $datetime, ?Carbon $now = null): string
    {
        $now ??= now();
        $seconds = $now->timestamp - Carbon::parse($datetime)->timestamp;

        if ($seconds < 60) {
            return "{$seconds}s geleden";
        }
        if ($seconds < 3600) {
            return round($seconds / 60).' min geleden';
        }
        if ($seconds < 86400) {
            return round($seconds / 3600).' uur geleden';
        }

        return round($seconds / 86400).' dagen geleden';
    }

    /**
     * Format bytes as human-readable string (MB or GB).
     */
    public static function formatBytes(int $bytes): string
    {
        return $bytes >= 1073741824
            ? number_format($bytes / 1073741824, 1).' GB'
            : number_format($bytes / 1048576, 0).' MB';
    }

    /**
     * Determine the status color for a metric value.
     */
    public static function statusForValue(float $value, string $metric): string
    {
        $thresholds = config("health.thresholds.{$metric}");

        if (! $thresholds) {
            return 'green';
        }

        if ($value >= $thresholds['critical']) {
            return 'red';
        }

        if ($value >= $thresholds['warning']) {
            return 'amber';
        }

        return 'green';
    }

    /**
     * Clean an error message: remove namespace prefixes, trim length.
     */
    private static function cleanErrorMessage(string $message): string
    {
        $message = preg_replace('/^[\w\\\\]+\\\\(\w+)/', '$1', $message);

        return str($message)->limit(200)->toString();
    }

    /**
     * Resolve the path to the current log file.
     */
    private static function currentLogPath(): ?string
    {
        $channel = config('logging.default');
        $channelConfig = config("logging.channels.{$channel}");

        // Handle stack channel — look for the first daily or single channel
        if (($channelConfig['driver'] ?? '') === 'stack') {
            foreach ($channelConfig['channels'] ?? [] as $subChannel) {
                $subConfig = config("logging.channels.{$subChannel}");
                if (in_array($subConfig['driver'] ?? '', ['daily', 'single'])) {
                    $channelConfig = $subConfig;
                    break;
                }
            }
        }

        $driver = $channelConfig['driver'] ?? 'single';
        $basePath = $channelConfig['path'] ?? storage_path('logs/laravel.log');

        if ($driver === 'daily') {
            $dir = dirname($basePath);
            $name = pathinfo($basePath, PATHINFO_FILENAME);
            $dated = $dir.'/'.$name.'-'.now()->format('Y-m-d').'.log';

            return file_exists($dated) ? $dated : null;
        }

        return file_exists($basePath) ? $basePath : null;
    }

    /**
     * Parse /proc/meminfo for MemTotal and MemAvailable.
     *
     * @return array{used: int, total: int, percent: float}|null
     */
    private static function memoryFromProc(): ?array
    {
        $content = @file_get_contents('/proc/meminfo');
        if ($content === false) {
            return null;
        }

        $total = null;
        $available = null;

        foreach (explode("\n", $content) as $line) {
            if (str_starts_with($line, 'MemTotal:')) {
                $total = (int) preg_replace('/\D/', '', $line) * 1024; // kB to bytes
            } elseif (str_starts_with($line, 'MemAvailable:')) {
                $available = (int) preg_replace('/\D/', '', $line) * 1024;
            }

            if ($total !== null && $available !== null) {
                break;
            }
        }

        if ($total === null || $available === null || $total === 0) {
            return null;
        }

        $used = $total - $available;

        return [
            'used' => $used,
            'total' => $total,
            'percent' => round(($used / $total) * 100, 1),
        ];
    }
}
