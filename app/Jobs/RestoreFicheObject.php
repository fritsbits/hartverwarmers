<?php

namespace App\Jobs;

use App\Services\FicheStorage\FicheObjectInventory;
use App\Services\FicheStorage\FicheStorage;
use App\Services\FicheStorage\MigrationRegister as Register;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Rollback direction: make sure the local disk holds the same bytes as the
 * bucket for one object. Missing or different local copies are replaced
 * atomically (temp file plus rename) and verified by SHA-256.
 */
class RestoreFicheObject implements ShouldQueue
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
        $storage->lock($this->role, $this->path, fn () => $this->restore($storage, $register, $inventory));
    }

    public function failed(?Throwable $exception): void
    {
        $register = app(Register::class);
        $object = $register->object($this->role, $this->path);

        $register->upsertObject($this->role, $this->path, [
            'local_state' => Register::STATE_FAILED,
            'error' => $exception?->getMessage(),
            'attempts' => ($object->attempts ?? 0) + 1,
            'queued_at' => null,
        ]);
        $register->recordError($exception?->getMessage() ?? 'Onbekende fout', $this->role, $this->path);
    }

    private function restore(FicheStorage $storage, Register $register, FicheObjectInventory $inventory): void
    {
        if ($register->isDeleted($this->role, $this->path)) {
            return;
        }

        if (! $inventory->isReferenced($this->role, $this->path)) {
            $register->upsertObject($this->role, $this->path, ['local_state' => Register::STATE_UNREFERENCED, 'queued_at' => null]);

            return;
        }

        $local = $storage->localDisk($this->role);
        $remote = $storage->remoteDisk($this->role);
        $object = $register->object($this->role, $this->path);
        $localHash = $local->exists($this->path) ? hash_file('sha256', $local->path($this->path)) : null;

        if (! $remote->exists($this->path)) {
            $matches = $localHash !== null && ($object?->expected_sha256 === null || $object->expected_sha256 === $localHash);

            $this->result($register, $object, $matches, $matches ? null : 'Remote ontbreekt en de lokale kopie is niet bevestigd.');

            return;
        }

        $remoteHash = $storage->hashOf($remote, $this->path);

        if ($localHash !== $remoteHash) {
            $target = $local->path($this->path);
            $directory = dirname($target);

            if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new RuntimeException("Could not create {$directory}.");
            }

            $temporary = $directory.'/.restore-'.Str::lower(Str::random(8));

            try {
                $storage->download($remote, $this->path, $temporary);

                if (hash_file('sha256', $temporary) !== $remoteHash) {
                    throw new RuntimeException('Gedownloade inhoud verschilt van de remote checksum.');
                }

                chmod($temporary, 0644);
                rename($temporary, $target);
            } finally {
                @unlink($temporary);
            }

            $localHash = hash_file('sha256', $target);
        }

        $this->result($register, $object, $localHash === $remoteHash, $localHash === $remoteHash ? null : 'Lokale kopie verschilt na herstel.');
    }

    private function result(Register $register, ?object $object, bool $verified, ?string $error): void
    {
        $register->upsertObject($this->role, $this->path, [
            'local_state' => $verified ? Register::STATE_VERIFIED : Register::STATE_FAILED,
            'local_verified_at' => $verified ? now()->timestamp : null,
            'error' => $error,
            'attempts' => $verified ? 0 : ($object->attempts ?? 0) + 1,
            'queued_at' => null,
        ]);

        if ($error !== null) {
            $register->recordError($error, $this->role, $this->path);
        }
    }
}
