<?php

namespace App\Services\FicheStorage;

use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Lists long-running artisan processes (queue workers, CLI commands) that
 * started before a given moment, so a mode change can wait until processes
 * running older code or older configuration are gone.
 */
class ProcessInspector
{
    /**
     * @return array<int, array{pid: int, started_at: int, command: string}>|null Null when the process list is unavailable.
     */
    public function artisanProcessesStartedBefore(int $timestamp): ?array
    {
        try {
            $result = Process::timeout(10)->run(['ps', '-eo', 'pid=,etime=,args=']);
        } catch (Throwable) {
            return null;
        }

        if (! $result->successful()) {
            return null;
        }

        $siteRoot = $this->siteRoot();
        $old = [];

        foreach (preg_split('/\R/', trim($result->output())) as $line) {
            if (! preg_match('/^\s*(\d+)\s+(?:(\d+)-)?(?:(\d+):)?(\d+):(\d+)\s+(.*)$/', $line, $matches)) {
                continue;
            }

            [, $pid, $days, $hours, $minutes, $seconds, $command] = $matches;

            if ((int) $pid === getmypid() || ! str_contains($command, 'artisan') || $this->isHarmless($command)) {
                continue;
            }

            if (str_contains($command, '/artisan') && ! str_contains($command, $siteRoot)) {
                continue;
            }

            $elapsed = ((int) $days * 86400) + ((int) $hours * 3600) + ((int) $minutes * 60) + (int) $seconds;
            $startedAt = now()->timestamp - $elapsed;

            if ($startedAt < $timestamp) {
                $old[] = ['pid' => (int) $pid, 'started_at' => $startedAt, 'command' => $command];
            }
        }

        return $old;
    }

    /**
     * Forge zero-downtime deploys run from <site>/releases/<id>; processes of
     * any release, or of the "current" symlink, belong to this site. Other
     * sites on the same server are ignored.
     */
    private function siteRoot(): string
    {
        $base = base_path();

        return basename(dirname($base)) === 'releases' ? dirname($base, 2) : $base;
    }

    /**
     * The scheduler itself starts every command as a fresh process with the
     * current configuration; it never writes fiche files.
     */
    private function isHarmless(string $command): bool
    {
        return str_contains($command, 'schedule:run')
            || str_contains($command, 'files:storage-')
            || str_contains($command, 'pail');
    }
}
