<?php

namespace App\Services\FicheStorage;

use Throwable;

/**
 * One report for /admin/gezondheid and files:storage-status, so following
 * the migration never needs a terminal.
 */
class StorageStatus
{
    public function __construct(
        private FicheStorage $storage,
        private MigrationRegister $register,
        private WriterDrain $drain,
        private StorageMigration $migration,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $report = [
            'mode' => (string) config('fiche-storage.mode'),
            'release' => WriterDrain::release(),
            'remote_configured' => $this->storage->remoteConfigured(),
        ];

        try {
            $firstSwitch = $this->register->meta('first_switch_at');

            return $report + [
                'observed' => $this->register->meta('mode'),
                'drain' => $this->drain->evaluate(),
                'workers' => $this->drain->workers(),
                'preflight' => $this->register->meta('preflight'),
                'smoketest' => $this->register->meta('smoketest'),
                'buckets' => $this->register->meta('bucket_health'),
                'runs' => [
                    StorageMigration::TO_REMOTE => $this->run(StorageMigration::TO_REMOTE),
                    StorageMigration::TO_LOCAL => $this->run(StorageMigration::TO_LOCAL),
                    LocalCleanup::KIND => $this->run(LocalCleanup::KIND),
                ],
                'unverified' => $this->register->unverifiedCount(),
                'fallback_hits' => $this->register->unresolvedFallbackHits(),
                'first_switch_at' => $firstSwitch,
                's3_authoritative_since' => $this->register->meta('s3_authoritative_since'),
                'earliest_cleanup_at' => $firstSwitch ? $firstSwitch + (int) config('fiche-storage.migration.retention_days') * 86400 : null,
                'last_error' => $this->register->meta('last_error'),
                'error' => null,
            ];
        } catch (Throwable $e) {
            report($e);

            return $report + ['error' => 'Migratieregister niet leesbaar: '.$e->getMessage()];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function run(string $kind): ?array
    {
        $run = $this->register->latestRun($kind);

        if ($run === null) {
            return null;
        }

        $summary = json_decode($run->summary ?? '{}', true) ?: [];
        $live = in_array($run->status, ['running', 'waiting'], true) && $kind !== LocalCleanup::KIND;

        return [
            'id' => $run->id,
            'status' => $run->status,
            'phase' => $run->phase,
            'started_at' => $run->started_at,
            'updated_at' => $run->updated_at,
            'finished_at' => $run->finished_at,
            'last_error' => $run->last_error,
            'summary' => $live ? $this->migration->progress($run) : $summary,
        ];
    }
}
