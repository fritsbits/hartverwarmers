<?php

namespace App\Services\FicheStorage;

use App\Enums\FicheStorageMode;
use App\Jobs\CleanupLocalFicheObjects;
use App\Models\Fiche;

/**
 * Removes local copies once the bucket is the only source of truth. Two
 * explicit steps: a dry run writes a plan, and only that plan can be
 * executed, item by item, re-checking reference and remote bytes right
 * before each delete. Unknown files are reported, never removed.
 */
class LocalCleanup
{
    public const KIND = 'cleanup';

    public const LEGACY_ZIP = 'legacy-zip';

    private const CHUNK = 50;

    private const UNKNOWN_SCAN_PREFIXES = ['files', 'file-previews', 'fiche-zips'];

    public function __construct(
        private FicheStorage $storage,
        private MigrationRegister $register,
        private FicheObjectInventory $inventory,
        private WriterDrain $drain,
        private BucketHealth $buckets,
    ) {}

    /**
     * @return array<int, string>
     */
    public function blockingReasons(): array
    {
        $reasons = [];

        if ($this->storage->mode() !== FicheStorageMode::S3 || $this->drain->observedMode() !== FicheStorageMode::S3) {
            $reasons[] = 'Opruimen kan alleen in modus s3, nadat de scheduler die modus heeft waargenomen.';
        }

        $firstSwitch = $this->register->meta('first_switch_at');
        if ($firstSwitch === null || now()->timestamp < $firstSwitch + (int) config('fiche-storage.migration.retention_days') * 86400) {
            $reasons[] = 'De bewaartermijn na de eerste omschakeling is nog niet voorbij.';
        }

        $drainedAt = $this->drain->drainedAt();
        if ($drainedAt === null) {
            $reasons[] = 'Processen die nog lokaal kunnen lezen of schrijven zijn niet uitgelopen.';
        }

        $run = $this->register->latestRun(StorageMigration::TO_REMOTE);
        $summary = json_decode($run->summary ?? '{}', true) ?: [];
        if ($run === null || $run->status !== 'verified' || ($summary['final_round_started_at'] ?? 0) < (int) $drainedAt) {
            $reasons[] = 'Er is geen geslaagde verificatie in modus s3. Voer eerst files:copy-to-object-storage --queued uit.';
        }

        $unverified = $this->register->unverifiedCount();
        if ($unverified > 0) {
            $reasons[] = "{$unverified} object(en) zijn niet geverifieerd in de bucket.";
        }

        if ($this->storage->remoteConfigured() && ! $this->buckets->healthy()) {
            $reasons[] = 'Een bucket is niet bereikbaar.';
        }

        if ($this->register->runs()->where('kind', self::KIND)->where('status', 'running')->exists()) {
            $reasons[] = 'Er loopt al een opruiming.';
        }

        return $reasons;
    }

    /**
     * @return array{ok: bool, reasons: array<int, string>, run_id: ?string, items: int, bytes: int, unknown: int, unknown_sample: array<int, string>}
     */
    public function plan(): array
    {
        $reasons = $this->blockingReasons();

        if ($reasons !== []) {
            return ['ok' => false, 'reasons' => $reasons, 'run_id' => null, 'items' => 0, 'bytes' => 0, 'unknown' => 0, 'unknown_sample' => []];
        }

        $runId = $this->register->createRun(self::KIND, ['status' => 'planned', 'phase' => 'planned']);
        $planned = [];

        $this->register->objects()
            ->where('state', MigrationRegister::STATE_VERIFIED)
            ->orderBy('id')
            ->chunk(500, function ($objects) use ($runId, &$planned) {
                $rows = [];

                foreach ($objects as $object) {
                    $local = $this->storage->localDisk($object->role);

                    if ($local->exists($object->path)) {
                        $rows[] = $this->item($runId, $object->role, $object->path, $local->size($object->path));
                        $planned[$object->path] = true;
                    }
                }

                if ($rows !== []) {
                    $this->register->cleanupItems()->insert($rows);
                }
            });

        $local = $this->storage->localDisk(FicheStorage::FILES);
        $zipRows = [];
        foreach (Fiche::withTrashed()->whereNotNull('zip_path')->pluck('zip_path') as $zipPath) {
            if (str_starts_with($zipPath, 'fiche-zips/') && ! isset($planned[$zipPath]) && $local->exists($zipPath)) {
                $zipRows[] = $this->item($runId, self::LEGACY_ZIP, $zipPath, $local->size($zipPath));
                $planned[$zipPath] = true;
            }
        }
        if ($zipRows !== []) {
            $this->register->cleanupItems()->insert($zipRows);
        }

        $unknown = [];
        foreach (self::UNKNOWN_SCAN_PREFIXES as $prefix) {
            if (! $local->directoryExists($prefix)) {
                continue;
            }

            foreach ($local->allFiles($prefix) as $path) {
                if (! isset($planned[$path])) {
                    $unknown[] = $path;
                }
            }
        }

        $items = $this->register->cleanupItems()->where('run_id', $runId);
        $result = [
            'ok' => true,
            'reasons' => [],
            'run_id' => $runId,
            'items' => (clone $items)->count(),
            'bytes' => (int) (clone $items)->sum('bytes'),
            'unknown' => count($unknown),
            'unknown_sample' => array_slice($unknown, 0, 20),
        ];

        $this->register->updateRun($runId, ['summary' => json_encode($result)]);

        return $result;
    }

    /**
     * @return array{ok: bool, reasons: array<int, string>}
     */
    public function execute(string $runId): array
    {
        $run = $this->register->run($runId);
        $latest = $this->register->latestRun(self::KIND);
        $reasons = [];

        if ($run === null || $run->kind !== self::KIND) {
            $reasons[] = "Onbekend opruimplan {$runId}.";
        } elseif ($run->status !== 'planned') {
            $reasons[] = "Plan {$runId} heeft status {$run->status}; maak een nieuw plan met --dry-run.";
        } elseif ($latest?->id !== $runId) {
            $reasons[] = "Plan {$runId} is niet het recentste plan ({$latest?->id}).";
        } elseif ($run->started_at < now()->timestamp - (int) config('fiche-storage.migration.cleanup_plan_valid_hours') * 3600) {
            $reasons[] = 'Dit plan is verlopen; maak een nieuw plan met --dry-run.';
        }

        $reasons = array_merge($reasons, $this->blockingReasons());

        if ($reasons !== []) {
            return ['ok' => false, 'reasons' => $reasons];
        }

        $this->register->updateRun($runId, ['status' => 'running', 'phase' => 'execute']);
        CleanupLocalFicheObjects::dispatch($runId);

        return ['ok' => true, 'reasons' => []];
    }

    /**
     * Delete one chunk of planned items. Returns whether items remain.
     */
    public function processChunk(string $runId): bool
    {
        $run = $this->register->run($runId);

        if ($run === null || $run->status !== 'running') {
            return false;
        }

        if ($this->storage->mode() !== FicheStorageMode::S3) {
            $this->register->updateRun($runId, ['status' => 'stopped', 'last_error' => 'Modus is niet langer s3.']);

            return false;
        }

        $items = $this->register->cleanupItems()->where('run_id', $runId)->where('state', 'planned')->limit(self::CHUNK)->get();
        $deletedPaths = [];

        foreach ($items as $item) {
            [$state, $reason] = $this->cleanItem($item);
            $this->register->cleanupItems()->where('id', $item->id)->update(['state' => $state, 'reason' => $reason]);

            if ($state === 'deleted') {
                $deletedPaths[$item->role === self::LEGACY_ZIP ? FicheStorage::FILES : $item->role][] = $item->path;
            }
        }

        foreach ($deletedPaths as $role => $paths) {
            $this->pruneEmptyDirectories($role, $paths);
        }

        if ($this->register->cleanupItems()->where('run_id', $runId)->where('state', 'planned')->exists()) {
            return true;
        }

        $all = $this->register->cleanupItems()->where('run_id', $runId);
        $summary = json_decode($run->summary ?? '{}', true) ?: [];
        $summary['deleted'] = (clone $all)->where('state', 'deleted')->count();
        $summary['freed_bytes'] = (int) (clone $all)->where('state', 'deleted')->sum('bytes');
        $summary['skipped'] = (clone $all)->where('state', 'skipped')->count();
        $summary['skipped_sample'] = (clone $all)->where('state', 'skipped')->limit(20)->get(['path', 'reason'])
            ->map(fn (object $row) => ['path' => $row->path, 'reason' => $row->reason])->all();

        $this->register->updateRun($runId, ['status' => 'done', 'phase' => 'done', 'finished_at' => now()->timestamp, 'summary' => json_encode($summary)]);

        return false;
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function cleanItem(object $item): array
    {
        if ($item->role === self::LEGACY_ZIP) {
            if (! str_starts_with($item->path, 'fiche-zips/')) {
                return ['skipped', 'Buiten de toegestane map.'];
            }

            $this->storage->localDisk(FicheStorage::FILES)->delete($item->path);

            return ['deleted', null];
        }

        return $this->storage->lock($item->role, $item->path, function () use ($item) {
            $object = $this->register->object($item->role, $item->path);

            if ($object === null || $object->state !== MigrationRegister::STATE_VERIFIED || $object->expected_sha256 === null) {
                return ['skipped', 'Niet (meer) geverifieerd.'];
            }

            if (! $this->inventory->isReferenced($item->role, $item->path)) {
                return ['skipped', 'Niet meer in gebruik; blijft staan voor controle.'];
            }

            $remote = $this->storage->remoteDisk($item->role);

            if (! $remote->exists($item->path) || $this->storage->hashOf($remote, $item->path) !== $object->expected_sha256) {
                $this->register->upsertObject($item->role, $item->path, ['state' => MigrationRegister::STATE_FAILED, 'error' => 'Remote integriteit niet bevestigd bij opruimen.']);

                return ['skipped', 'Remote integriteit niet bevestigd.'];
            }

            $this->storage->localDisk($item->role)->delete($item->path);

            return ['deleted', null];
        });
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function pruneEmptyDirectories(string $role, array $paths): void
    {
        $disk = $this->storage->localDisk($role);

        foreach (array_unique(array_map('dirname', $paths)) as $directory) {
            if (str_contains($directory, '/') && $disk->directoryExists($directory)
                && $disk->files($directory) === [] && $disk->directories($directory) === []) {
                $disk->deleteDirectory($directory);
            }
        }
    }

    /**
     * @return array{run_id: string, role: string, path: string, bytes: int, state: string, reason: null}
     */
    private function item(string $runId, string $role, string $path, int $bytes): array
    {
        return ['run_id' => $runId, 'role' => $role, 'path' => $path, 'bytes' => $bytes, 'state' => 'planned', 'reason' => null];
    }
}
