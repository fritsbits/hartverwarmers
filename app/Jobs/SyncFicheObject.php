<?php

namespace App\Jobs;

use App\Services\FicheStorage\FicheObjectInventory;
use App\Services\FicheStorage\FicheStorage;
use App\Services\FicheStorage\MigrationRegister as Register;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Copy one object from local storage to the bucket, or check one remote
 * write, and verify the remote bytes by reading them back. Size or ETag
 * alone never counts as verified: only a matching SHA-256 does.
 *
 * Runs under the object lock, so a writer or purger never races it. Once
 * remote is the source of truth, a local copy never overwrites newer
 * remote bytes.
 */
class SyncFicheObject implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public function __construct(
        public string $role,
        public string $path,
    ) {}

    public function handle(FicheStorage $storage, Register $register, FicheObjectInventory $inventory): void
    {
        if (! $storage->remoteConfigured()) {
            return;
        }

        $regenerateFileId = $storage->lock($this->role, $this->path, fn () => $this->sync($storage, $register, $inventory));

        if ($regenerateFileId !== null && Cache::add("fiche-storage:regenerate:{$regenerateFileId}", true, now()->addHours(6))) {
            GenerateFilePreview::dispatch($regenerateFileId);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $register = app(Register::class);
        $object = $register->object($this->role, $this->path);

        $register->upsertObject($this->role, $this->path, [
            'state' => Register::STATE_FAILED,
            'error' => $exception?->getMessage(),
            'attempts' => ($object->attempts ?? 0) + 1,
            'queued_at' => null,
        ]);
        $register->recordError($exception?->getMessage() ?? 'Onbekende fout', $this->role, $this->path);
    }

    /**
     * @return int|null File id whose previews must be regenerated.
     */
    private function sync(FicheStorage $storage, Register $register, FicheObjectInventory $inventory): ?int
    {
        $object = $register->object($this->role, $this->path);

        if ($register->isDeleted($this->role, $this->path)) {
            return null;
        }

        $recentlyWritten = ($object->written_at ?? 0) > now()->subDay()->timestamp;

        if (! $recentlyWritten && ! $inventory->isReferenced($this->role, $this->path)) {
            $register->upsertObject($this->role, $this->path, ['state' => Register::STATE_UNREFERENCED, 'queued_at' => null]);

            return null;
        }

        $local = $storage->localDisk($this->role);
        $remote = $storage->remoteDisk($this->role);
        $expected = $object->expected_sha256 ?? null;
        $remoteHash = $remote->exists($this->path) ? $storage->hashOf($remote, $this->path) : null;

        if ($local->exists($this->path)) {
            $localPath = $local->path($this->path);
            $localHash = hash_file('sha256', $localPath);
            $localFacts = ['local_sha256' => $localHash, 'local_mtime' => filemtime($localPath), 'size' => filesize($localPath)];

            if ($remoteHash === $localHash) {
                $this->verified($register, $localHash, $localFacts);

                return null;
            }

            if ($remoteHash !== null && $register->meta('s3_authoritative_since') !== null) {
                $this->verifyRemoteOnly($register, $remoteHash, $expected, $localFacts);

                return null;
            }

            $stream = fopen($localPath, 'rb');

            try {
                $remote->writeStream($this->path, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $readBack = $storage->hashOf($remote, $this->path);

            if ($readBack === $localHash) {
                $this->verified($register, $localHash, $localFacts);
            } else {
                $this->failure($register, $object, 'Teruggelezen inhoud verschilt van de lokale bron.', ['remote_sha256' => $readBack]);
            }

            return null;
        }

        if ($remoteHash !== null) {
            $this->verifyRemoteOnly($register, $remoteHash, $expected, []);

            return null;
        }

        $register->upsertObject($this->role, $this->path, [
            'state' => Register::STATE_MISSING,
            'error' => 'Lokaal en remote niet gevonden.',
            'queued_at' => null,
        ]);

        return $this->role === FicheStorage::PREVIEWS
            ? FicheObjectInventory::fileIdFromPreviewPath($this->path)
            : null;
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private function verifyRemoteOnly(Register $register, string $remoteHash, ?string $expected, array $facts): void
    {
        if ($expected !== null && $remoteHash === $expected) {
            $this->verified($register, $remoteHash, $facts);

            return;
        }

        $this->failure(
            $register,
            $register->object($this->role, $this->path),
            $expected === null ? 'Alleen remote aanwezig, zonder verwachte checksum.' : 'Remote inhoud wijkt af van de verwachte checksum.',
            array_merge($facts, ['remote_sha256' => $remoteHash]),
        );
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private function verified(Register $register, string $sha256, array $facts): void
    {
        $register->upsertObject($this->role, $this->path, array_merge($facts, [
            'state' => Register::STATE_VERIFIED,
            'expected_sha256' => $sha256,
            'remote_sha256' => $sha256,
            'verified_at' => now()->timestamp,
            'attempts' => 0,
            'error' => null,
            'queued_at' => null,
        ]));
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private function failure(Register $register, ?object $object, string $error, array $facts): void
    {
        $register->upsertObject($this->role, $this->path, array_merge($facts, [
            'state' => Register::STATE_FAILED,
            'error' => $error,
            'attempts' => ($object->attempts ?? 0) + 1,
            'verified_at' => null,
            'queued_at' => null,
        ]));
        $register->recordError($error, $this->role, $this->path);
    }
}
