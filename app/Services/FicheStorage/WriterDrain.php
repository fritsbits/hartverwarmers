<?php

namespace App\Services\FicheStorage;

use App\Enums\FicheStorageMode;
use Throwable;

/**
 * Tracks which storage mode is live and whether every process that might
 * still run with the previous mode is gone. A queue:restart signal alone
 * proves nothing: workers finish their current job first, CLI commands keep
 * running and web requests are in flight. A mode counts as drained after a
 * grace period, once no artisan process predates the mode change and every
 * worker heartbeat reports the new mode.
 */
class WriterDrain
{
    private const WORKER_STALE_SECONDS = 600;

    private static ?int $lastWorkerBeat = null;

    public function __construct(
        private MigrationRegister $register,
        private ProcessInspector $processes,
    ) {}

    /**
     * Note the mode the running release uses. Called every minute by the
     * scheduler, so mode history is recorded without anyone watching.
     */
    public function observe(FicheStorageMode $mode): void
    {
        $current = $this->register->meta('mode');

        if (($current['mode'] ?? FicheStorageMode::Local->value) !== $mode->value || $current === null) {
            $this->register->putMeta('mode', [
                'mode' => $mode->value,
                'since' => now()->timestamp,
                'drained_at' => null,
                'previous' => $current['mode'] ?? null,
            ]);
        }

        if ($mode->remoteIsAuthoritative()) {
            if ($this->register->meta('s3_authoritative_since') === null) {
                $this->register->putMeta('s3_authoritative_since', now()->timestamp);
            }

            if ($mode === FicheStorageMode::S3Fallback && $this->register->meta('first_switch_at') === null) {
                $this->register->putMeta('first_switch_at', now()->timestamp);
            }
        }
    }

    public function observedMode(): FicheStorageMode
    {
        return FicheStorageMode::tryFrom($this->register->meta('mode')['mode'] ?? '') ?? FicheStorageMode::Local;
    }

    public function observedSince(): ?int
    {
        return $this->register->meta('mode')['since'] ?? null;
    }

    public function drainedAt(): ?int
    {
        return $this->register->meta('mode')['drained_at'] ?? null;
    }

    /**
     * @return array{drained: bool, reasons: array<int, string>}
     */
    public function evaluate(): array
    {
        $since = $this->observedSince();

        if ($since === null) {
            return ['drained' => false, 'reasons' => ['De actieve modus is nog niet waargenomen door de scheduler.']];
        }

        $reasons = [];
        $graceEnds = $since + (int) config('fiche-storage.migration.drain_grace_minutes') * 60;

        if (now()->timestamp < $graceEnds) {
            $reasons[] = 'Wachttijd na de moduswissel loopt tot '.date('H:i', $graceEnds).'.';
        }

        $old = $this->processes->artisanProcessesStartedBefore($since);

        if ($old === null) {
            $reasons[] = 'De proceslijst is niet leesbaar; oude processen zijn niet uit te sluiten.';
        } elseif ($old !== []) {
            $reasons[] = count($old).' artisan-proces(sen) draaien nog van vóór de moduswissel: '
                .implode(', ', array_map(fn (array $process) => $process['pid'], $old)).'.';
        }

        $mode = $this->observedMode()->value;
        foreach ($this->workers() as $pid => $worker) {
            if ($worker['mode'] !== $mode) {
                $reasons[] = "Worker {$pid} meldt nog modus {$worker['mode']}.";
            }
        }

        return ['drained' => $reasons === [], 'reasons' => $reasons];
    }

    public function markDrainedIfReady(): void
    {
        $meta = $this->register->meta('mode');

        if ($meta === null || ($meta['drained_at'] ?? null) !== null) {
            return;
        }

        if ($this->evaluate()['drained']) {
            $meta['drained_at'] = now()->timestamp;
            $this->register->putMeta('mode', $meta);
        }
    }

    /**
     * Heartbeat from a queue worker loop, at most once a minute per process.
     * Never lets a register problem stop a worker.
     */
    public function recordWorker(): void
    {
        if (self::$lastWorkerBeat !== null && now()->timestamp - self::$lastWorkerBeat < 60) {
            return;
        }

        self::$lastWorkerBeat = now()->timestamp;

        try {
            $workers = $this->workers();
            $workers[(string) getmypid()] = [
                'mode' => (string) config('fiche-storage.mode'),
                'release' => self::release(),
                'at' => now()->timestamp,
            ];
            $this->register->putMeta('workers', $workers);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Workers that reported within the last ten minutes.
     *
     * @return array<string, array{mode: string, release: string, at: int}>
     */
    public function workers(): array
    {
        return array_filter(
            $this->register->meta('workers', []),
            fn (array $worker) => $worker['at'] >= now()->timestamp - self::WORKER_STALE_SECONDS,
        );
    }

    /**
     * Name of the deployed release directory (Forge: releases/<id>).
     */
    public static function release(): string
    {
        return basename(base_path());
    }
}
