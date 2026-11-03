<?php

namespace App\Console\Commands;

use App\Jobs\AdvanceFicheStorageRun;
use App\Jobs\CleanupLocalFicheObjects;
use App\Jobs\SyncFicheObject;
use App\Services\FicheStorage\FicheStorage;
use App\Services\FicheStorage\LocalCleanup;
use App\Services\FicheStorage\MigrationRegister;
use App\Services\FicheStorage\StorageMigration;
use App\Services\FicheStorage\WriterDrain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Scheduled every minute. Records the live storage mode, notices when old
 * processes are gone, resumes stalled runs and unverified writes, and
 * removes abandoned workspaces. Never transfers or deletes fiche files
 * itself.
 */
class SuperviseFicheStorageCommand extends Command
{
    protected $signature = 'files:storage-supervise';

    protected $description = 'Bewaak de fiche-opslag: modus, vastgelopen runs, onbevestigde writes en werkmappen';

    private const STALE_SECONDS = 600;

    private const PENDING_BATCH = 50;

    public function handle(FicheStorage $storage, WriterDrain $drain, MigrationRegister $register): int
    {
        $mode = $storage->mode();
        $drain->observe($mode);
        $drain->markDrainedIfReady();

        if ($storage->remoteConfigured()) {
            $this->resumeRuns($register);

            if ($mode->usesRemote()) {
                $this->dispatchPendingWrites($register);
            }
        }

        $removed = $this->removeAbandonedWorkspaces($storage);

        $this->components->info("Modus {$mode->value} waargenomen; {$removed} verlaten werkmap(pen) verwijderd.");

        return self::SUCCESS;
    }

    private function resumeRuns(MigrationRegister $register): void
    {
        $stale = now()->timestamp - self::STALE_SECONDS;

        $register->runs()
            ->whereIn('kind', [StorageMigration::TO_REMOTE, StorageMigration::TO_LOCAL])
            ->where(fn ($query) => $query->where('status', 'waiting')->orWhere(fn ($running) => $running->where('status', 'running')->where('updated_at', '<', $stale)))
            ->pluck('id')
            ->each(fn (string $id) => AdvanceFicheStorageRun::dispatch($id));

        $register->runs()
            ->where('kind', LocalCleanup::KIND)
            ->where('status', 'running')
            ->where('updated_at', '<', $stale)
            ->pluck('id')
            ->each(fn (string $id) => CleanupLocalFicheObjects::dispatch($id));
    }

    /**
     * Writes outside a run are verified by the job putFile dispatched; this
     * catches the ones whose job was lost.
     */
    private function dispatchPendingWrites(MigrationRegister $register): void
    {
        $stale = now()->timestamp - 900;

        $objects = $register->objects()
            ->where('state', MigrationRegister::STATE_PENDING)
            ->whereNotNull('written_at')
            ->where('written_at', '<', now()->timestamp - 300)
            ->where('attempts', '<', (int) config('fiche-storage.migration.max_attempts'))
            ->where(fn ($query) => $query->whereNull('queued_at')->orWhere('queued_at', '<', $stale))
            ->limit(self::PENDING_BATCH)
            ->get(['role', 'path']);

        foreach ($objects as $object) {
            $register->upsertObject($object->role, $object->path, ['queued_at' => now()->timestamp]);
            SyncFicheObject::dispatch($object->role, $object->path);
        }
    }

    private function removeAbandonedWorkspaces(FicheStorage $storage): int
    {
        $root = $storage->workspaceRoot();
        $cutoff = now()->subMinutes((int) config('fiche-storage.workspace.max_age_minutes'))->timestamp;
        $removed = 0;

        foreach (array_merge(File::directories($root), File::files($root)) as $entry) {
            $path = is_string($entry) ? $entry : $entry->getPathname();

            if (filemtime($path) < $cutoff) {
                is_dir($path) ? File::deleteDirectory($path) : File::delete($path);
                $removed++;
            }
        }

        return $removed;
    }
}
