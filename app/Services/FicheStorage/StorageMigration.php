<?php

namespace App\Services\FicheStorage;

use App\Jobs\AdvanceFicheStorageRun;
use App\Jobs\RestoreFicheObject;
use App\Jobs\SyncFicheObject;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Resumable copy runs in both directions:
 *
 *   to-remote  inventory every referenced object, copy it to the bucket and
 *              verify the bytes; used before trusting remote storage
 *   to-local   restore every referenced object from the bucket; used for a
 *              full rollback to local storage
 *
 * A run is advanced by AdvanceFicheStorageRun, one bounded step at a time,
 * with all progress in the MigrationRegister. A crashed worker or a deploy
 * only delays the next step; files:storage-supervise resumes stalled runs.
 */
class StorageMigration
{
    public const TO_REMOTE = 'to-remote';

    public const TO_LOCAL = 'to-local';

    private const QUEUED_TIMEOUT_SECONDS = 900;

    public function __construct(
        private FicheStorage $storage,
        private MigrationRegister $register,
        private FicheObjectInventory $inventory,
        private WriterDrain $drain,
    ) {}

    /**
     * Start a run, or resume the active one. Never starts two runs at once.
     *
     * @return array{run_id: string, resumed: bool}
     */
    public function start(string $kind): array
    {
        if (! in_array($kind, [self::TO_REMOTE, self::TO_LOCAL], true)) {
            throw new RuntimeException("Unknown migration kind {$kind}.");
        }

        $result = Cache::lock('fiche-storage:migration-start', 30)->block(10, function () use ($kind) {
            $active = $this->register->runs()
                ->whereIn('kind', [self::TO_REMOTE, self::TO_LOCAL])
                ->whereIn('status', ['running', 'waiting'])
                ->first();

            if ($active !== null && $active->kind !== $kind) {
                throw new RuntimeException("Er loopt al een {$active->kind}-run ({$active->id}); wacht tot die klaar is.");
            }

            if ($active !== null) {
                return ['run_id' => $active->id, 'resumed' => true];
            }

            return ['run_id' => $this->register->createRun($kind), 'resumed' => false];
        });

        AdvanceFicheStorageRun::dispatch($result['run_id']);

        return $result;
    }

    /**
     * Perform one step and schedule the next. Only one pump per run works at
     * a time; a duplicate dispatch returns without doing anything.
     */
    public function advance(string $runId): void
    {
        $lock = Cache::lock("fiche-storage:pump:{$runId}", 300);

        if (! $lock->get()) {
            return;
        }

        try {
            $delay = $this->step($runId);
        } finally {
            $lock->release();
        }

        if ($delay !== null) {
            AdvanceFicheStorageRun::dispatch($runId)->delay(now()->addSeconds($delay));
        }
    }

    /**
     * @return int|null Seconds until the next step, or null when the run waits or ended.
     */
    private function step(string $runId): ?int
    {
        $run = $this->register->run($runId);

        if ($run === null || ! in_array($run->status, ['running', 'waiting'], true)) {
            return null;
        }

        return match ($run->phase) {
            'inventory' => $this->inventoryStep($run),
            'transfer' => $this->transferStep($run),
            'settle' => $this->settleStep($run),
            default => null,
        };
    }

    private function inventoryStep(object $run): int
    {
        $chunk = $this->inventory->chunkAfter((int) $run->cursor, (int) config('fiche-storage.migration.inventory_chunk'));

        foreach ($chunk['objects'] as $object) {
            $this->note($run, $object['role'], $object['path']);
        }

        if ($chunk['last_id'] === null) {
            $this->register->updateRun($run->id, ['phase' => 'transfer', 'cursor' => 0, 'status' => 'running']);
        } else {
            $this->register->updateRun($run->id, ['cursor' => $chunk['last_id'], 'status' => 'running']);
        }

        return 0;
    }

    /**
     * Mark an inventoried object as part of this round, and as needing work
     * unless an earlier verification still holds for the same source.
     */
    private function note(object $run, string $role, string $path): void
    {
        $object = $this->register->object($role, $path);

        if ($object !== null && in_array($object->state, [MigrationRegister::STATE_DELETING, MigrationRegister::STATE_DELETED], true)) {
            return;
        }

        $attributes = ['seen_run' => $run->id, 'seen_round' => $run->round];
        $local = $this->storage->localDisk($role);
        $localPath = $local->exists($path) ? $local->path($path) : null;

        if ($run->kind === self::TO_REMOTE) {
            $stillValid = $object !== null
                && $object->state === MigrationRegister::STATE_VERIFIED
                && ($localPath !== null
                    ? filemtime($localPath) === $object->local_mtime && filesize($localPath) === $object->size
                    : $object->expected_sha256 !== null);

            if (! $stillValid) {
                $attributes += ['state' => MigrationRegister::STATE_PENDING, 'attempts' => 0, 'verified_at' => null];
            }
        } else {
            $attributes += ['local_state' => MigrationRegister::STATE_PENDING, 'attempts' => 0];
        }

        $this->register->upsertObject($role, $path, $attributes);
    }

    private function transferStep(object $run): int
    {
        $batch = (int) config('fiche-storage.migration.copy_batch');
        $staleBefore = now()->timestamp - self::QUEUED_TIMEOUT_SECONDS;

        $inFlight = $this->needsWork($run)->where('queued_at', '>=', $staleBefore)->count();
        $candidates = $this->needsWork($run)
            ->where(fn (Builder $query) => $query->whereNull('queued_at')->orWhere('queued_at', '<', $staleBefore))
            ->limit(max(0, $batch - $inFlight))
            ->get(['role', 'path']);

        foreach ($candidates as $candidate) {
            $this->register->upsertObject($candidate->role, $candidate->path, ['queued_at' => now()->timestamp]);
        }

        foreach ($candidates as $candidate) {
            $run->kind === self::TO_REMOTE
                ? SyncFicheObject::dispatch($candidate->role, $candidate->path)
                : RestoreFicheObject::dispatch($candidate->role, $candidate->path);
        }

        if ($this->needsWork($run)->exists()) {
            return (int) config('fiche-storage.migration.pump_delay_seconds');
        }

        $this->register->updateRun($run->id, ['phase' => 'settle']);

        return 0;
    }

    /**
     * A round started before old writers were gone may have missed their
     * writes: inventory again. Afterwards every writer records its own
     * writes, so no further round is needed.
     */
    private function settleStep(object $run): ?int
    {
        $drainedAt = $this->drain->drainedAt();

        if ($drainedAt === null) {
            $this->register->updateRun($run->id, [
                'status' => 'waiting',
                'last_error' => 'Wacht tot oude processen zijn uitgelopen: '.implode(' ', $this->drain->evaluate()['reasons']),
            ]);

            return null;
        }

        if ($run->round_started_at < $drainedAt && $run->round < (int) config('fiche-storage.migration.max_rounds')) {
            $this->register->updateRun($run->id, [
                'status' => 'running',
                'phase' => 'inventory',
                'round' => $run->round + 1,
                'cursor' => 0,
                'round_started_at' => now()->timestamp,
                'last_error' => null,
            ]);

            return 0;
        }

        $this->finalize($run);

        return null;
    }

    private function finalize(object $run): void
    {
        $blocking = $run->kind === self::TO_REMOTE
            ? $this->register->unverifiedCount()
            : $this->register->objects()
                ->where('seen_run', $run->id)
                ->where('seen_round', $run->round)
                ->whereNotIn('state', [MigrationRegister::STATE_DELETING, MigrationRegister::STATE_DELETED])
                ->where(fn (Builder $query) => $query->whereNull('local_state')->orWhereNotIn('local_state', [MigrationRegister::STATE_VERIFIED, MigrationRegister::STATE_UNREFERENCED]))
                ->count();

        $verified = $blocking === 0 && $run->round_started_at >= (int) $this->drain->drainedAt();

        $this->register->updateRun($run->id, [
            'status' => $verified ? 'verified' : 'blocked',
            'phase' => 'done',
            'finished_at' => now()->timestamp,
            'last_error' => $verified ? null : "{$blocking} object(en) niet geverifieerd.",
            'summary' => json_encode(array_merge($this->progress($run), [
                'final_round_started_at' => $run->round_started_at,
                'mode' => $this->storage->mode()->value,
            ])),
        ]);
    }

    /**
     * Objects this run still has to process in the current round.
     */
    private function needsWork(object $run): Builder
    {
        $maxAttempts = (int) config('fiche-storage.migration.max_attempts');
        $stateColumn = $run->kind === self::TO_REMOTE ? 'state' : 'local_state';

        return $this->register->objects()
            ->where('attempts', '<', $maxAttempts)
            ->whereNotIn('state', [MigrationRegister::STATE_DELETING, MigrationRegister::STATE_DELETED])
            ->where(function (Builder $query) use ($run, $stateColumn) {
                $query->where(fn (Builder $seen) => $seen
                    ->where('seen_run', $run->id)
                    ->where('seen_round', $run->round)
                    ->whereIn($stateColumn, [MigrationRegister::STATE_PENDING, MigrationRegister::STATE_FAILED]));

                if ($run->kind === self::TO_REMOTE) {
                    $query->orWhere(fn (Builder $written) => $written
                        ->whereNotNull('written_at')
                        ->where('state', MigrationRegister::STATE_PENDING));
                }
            });
    }

    /**
     * Counts and bytes for the run's current round, for status and health.
     *
     * @return array{round: int, total: int, bytes: int, verified_bytes: int, states: array<string, int>, missing: array<int, string>, failed: array<int, array{path: string, error: ?string}>}
     */
    public function progress(object $run): array
    {
        $stateColumn = $run->kind === self::TO_REMOTE ? 'state' : 'local_state';
        $scope = fn () => $this->register->objects()->where('seen_run', $run->id)->where('seen_round', $run->round);

        $states = $scope()
            ->selectRaw("coalesce({$stateColumn}, 'pending') as s, count(*) as c")
            ->groupBy('s')
            ->pluck('c', 's')
            ->map(fn ($count) => (int) $count)
            ->all();

        return [
            'round' => (int) $run->round,
            'total' => array_sum($states),
            'bytes' => (int) $scope()->sum('size'),
            'verified_bytes' => (int) $scope()->where($stateColumn, MigrationRegister::STATE_VERIFIED)->sum('size'),
            'states' => $states,
            'missing' => $scope()->where($stateColumn, MigrationRegister::STATE_MISSING)->limit(20)->pluck('path')->all(),
            'failed' => $scope()->where($stateColumn, MigrationRegister::STATE_FAILED)->limit(20)->get(['path', 'error'])
                ->map(fn (object $row) => ['path' => $row->path, 'error' => $row->error])->all(),
        ];
    }
}
