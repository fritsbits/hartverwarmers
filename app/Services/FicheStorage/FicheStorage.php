<?php

namespace App\Services\FicheStorage;

use App\Enums\FicheStorageMode;
use App\Jobs\SyncFicheObject;
use Closure;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File as LocalFiles;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Throwable;

/**
 * The only place that decides which physical disk holds a fiche file or
 * preview. Business code asks for a role ("files" or "previews") and a
 * stored key; the configured FicheStorageMode picks local disk, remote
 * bucket or both. See config/fiche-storage.php for the modes.
 */
class FicheStorage
{
    public const FILES = 'files';

    public const PREVIEWS = 'previews';

    public function __construct(
        private MigrationRegister $register,
        private ObjectVersions $versions,
    ) {}

    public function mode(): FicheStorageMode
    {
        $configured = config('fiche-storage.mode');

        return FicheStorageMode::tryFrom((string) $configured)
            ?? throw new RuntimeException("Unknown FICHE_STORAGE_MODE '{$configured}'.");
    }

    public function disk(string $role, string $location): FilesystemAdapter
    {
        $name = config("fiche-storage.disks.{$role}.{$location}")
            ?? throw new RuntimeException("Unknown fiche storage disk {$role}.{$location}.");

        return Storage::disk($name);
    }

    public function localDisk(string $role): FilesystemAdapter
    {
        return $this->disk($role, 'local');
    }

    public function remoteDisk(string $role): FilesystemAdapter
    {
        return $this->disk($role, 'remote');
    }

    /**
     * Whether the remote credentials and bucket names are filled in.
     */
    public function remoteConfigured(): bool
    {
        foreach ([self::FILES, self::PREVIEWS] as $role) {
            $config = config('filesystems.disks.'.config("fiche-storage.disks.{$role}.remote"));

            foreach (['key', 'secret', 'bucket', 'endpoint', 'region'] as $key) {
                if (blank($config[$key] ?? null)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Store an uploaded fiche file and return its key (files/<hash>.<ext>).
     */
    public function storeUpload(UploadedFile $upload): string
    {
        $path = 'files/'.$upload->hashName();

        $this->putFile(self::FILES, $path, $upload->getRealPath());

        return $path;
    }

    /**
     * Write a local file to every disk the current mode writes to. Throws
     * when any write fails, so callers never publish a half-stored object.
     */
    public function putFile(string $role, string $path, string $localPath): void
    {
        $mode = $this->mode();

        if (! is_file($localPath)) {
            throw new RuntimeException("Cannot store {$role}:{$path}, source {$localPath} does not exist.");
        }

        $sha256 = $mode->usesRemote() ? hash_file('sha256', $localPath) : null;

        $this->lock($role, $path, function () use ($mode, $role, $path, $localPath, $sha256) {
            foreach ($mode->writeTargets() as $location) {
                $stream = fopen($localPath, 'rb');

                if ($stream === false) {
                    throw new RuntimeException("Cannot open {$localPath} for reading.");
                }

                try {
                    $written = $this->disk($role, $location)->writeStream($path, $stream);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }

                if ($written === false) {
                    throw new RuntimeException("Could not write {$role}:{$path} to {$location} storage.");
                }
            }

            if ($sha256 !== null) {
                $this->register->recordWrite($role, $path, $sha256, (int) filesize($localPath));
            }
        });

        $this->forgetExistence($role, $path);

        if ($sha256 !== null) {
            SyncFicheObject::dispatch($role, $path);
        }
    }

    public function exists(string $role, string $path): bool
    {
        return $this->locate($role, $path) !== null;
    }

    /**
     * Find the disk that holds the object, following the mode's read order.
     * Network and permission errors propagate; only a clean "not found"
     * moves on to the next disk.
     *
     * @return array{location: 'local'|'remote', disk: FilesystemAdapter}|null
     */
    public function locate(string $role, string $path): ?array
    {
        $mode = $this->mode();

        foreach ($mode->readOrder() as $index => $location) {
            $disk = $this->disk($role, $location);

            if ($disk->exists($path)) {
                if ($index > 0 && $mode === FicheStorageMode::S3Fallback) {
                    $this->register->recordFallbackHit($role, $path);
                }

                return ['location' => $location, 'disk' => $disk];
            }
        }

        return null;
    }

    public function size(string $role, string $path): int
    {
        $found = $this->locate($role, $path) ?? throw ObjectMissing::at($role, $path);

        return $found['disk']->size($path);
    }

    /**
     * Run $callback with a real local path to the object. A remote object is
     * streamed into a private workspace that is removed afterwards; an
     * existing local original is used in place and never removed.
     *
     * @template T
     *
     * @param  Closure(string): T  $callback
     * @return T
     */
    public function withLocalCopy(string $role, string $path, Closure $callback): mixed
    {
        $found = $this->locate($role, $path) ?? throw ObjectMissing::at($role, $path);

        if ($found['location'] === 'local') {
            return $callback($found['disk']->path($path));
        }

        return $this->withWorkspace(function (string $workspace) use ($found, $path, $callback) {
            $target = $workspace.'/'.$this->workspaceName($path);
            $this->download($found['disk'], $path, $target);

            return $callback($target);
        });
    }

    /**
     * Run $callback with a fresh private directory for several input and
     * output files. The directory is removed when the callback ends, also
     * on failure.
     *
     * @template T
     *
     * @param  Closure(string): T  $callback
     * @return T
     */
    public function withWorkspace(Closure $callback): mixed
    {
        $workspace = $this->createWorkspace();

        try {
            return $callback($workspace);
        } finally {
            LocalFiles::deleteDirectory($workspace);
        }
    }

    /**
     * Create a private directory under the workspace root. The caller owns
     * it; abandoned ones are removed by files:storage-supervise.
     */
    public function createWorkspace(string $prefix = 'ws'): string
    {
        $root = $this->workspaceRoot();
        $workspace = $root.'/'.$prefix.'-'.now()->format('YmdHis').'-'.Str::lower(Str::random(12));

        if (! mkdir($workspace, 0700) && ! is_dir($workspace)) {
            throw new RuntimeException("Could not create workspace {$workspace}.");
        }

        return $workspace;
    }

    public function workspaceRoot(): string
    {
        $root = config('fiche-storage.workspace.root');

        if (! is_dir($root) && ! mkdir($root, 0700, true) && ! is_dir($root)) {
            throw new RuntimeException("Could not create workspace root {$root}.");
        }

        return $root;
    }

    /**
     * Stream an object from a disk to a local file without loading it in memory.
     */
    public function download(FilesystemAdapter $disk, string $path, string $target): void
    {
        $source = $disk->readStream($path);

        if (! is_resource($source)) {
            throw new RuntimeException("Could not read {$path}.");
        }

        $destination = fopen($target, 'wb');

        try {
            if ($destination === false || stream_copy_to_stream($source, $destination) === false) {
                throw new RuntimeException("Could not copy {$path} to {$target}.");
            }
        } finally {
            fclose($source);

            if (is_resource($destination)) {
                fclose($destination);
            }
        }
    }

    /**
     * Public URL for a preview image or thumbnail.
     */
    public function previewUrl(string $path): string
    {
        return $this->firstAvailablePreviewUrl([$path]);
    }

    /**
     * URL of the first preview path that exists, for "thumbnail if there is
     * one, otherwise the full image". Falls back to the last candidate.
     *
     * @param  array<int, string>  $paths
     */
    public function firstAvailablePreviewUrl(array $paths): string
    {
        $local = $this->localDisk(self::PREVIEWS);
        $last = end($paths);

        switch ($this->mode()) {
            case FicheStorageMode::Local:
                foreach ($paths as $path) {
                    if ($local->exists($path)) {
                        return $local->url($path);
                    }
                }

                return $local->url($last);

            case FicheStorageMode::Mirror:
                foreach ($paths as $path) {
                    if ($local->exists($path)) {
                        return $local->url($path);
                    }
                }

                foreach ($paths as $path) {
                    if ($this->remotePreviewExists($path)) {
                        return $this->remoteDisk(self::PREVIEWS)->url($path);
                    }
                }

                return $local->url($last);

            case FicheStorageMode::S3Fallback:
                foreach ($paths as $path) {
                    if ($this->remotePreviewExists($path)) {
                        return $this->remoteDisk(self::PREVIEWS)->url($path);
                    }
                }

                foreach ($paths as $path) {
                    if ($local->exists($path)) {
                        $this->register->recordFallbackHit(self::PREVIEWS, $path);

                        return $local->url($path);
                    }
                }

                return $this->remoteDisk(self::PREVIEWS)->url($last);

            case FicheStorageMode::S3:
                return $this->remoteDisk(self::PREVIEWS)->url($paths[0]);
        }
    }

    /**
     * Short-lived download link that names the file like the original upload.
     */
    public function temporaryDownloadUrl(string $path, string $filename, string $mimeType): string
    {
        return $this->remoteDisk(self::FILES)->temporaryUrl(
            $path,
            now()->addMinutes((int) config('fiche-storage.downloads.presigned_minutes')),
            [
                'ResponseContentDisposition' => self::attachmentDisposition($filename),
                'ResponseContentType' => $mimeType,
            ],
        );
    }

    /**
     * Content-Disposition for an uploaded filename: never the raw name, always
     * with an ASCII fallback for clients without RFC 6266 support.
     */
    public static function attachmentDisposition(string $filename): string
    {
        $clean = trim(preg_replace('/[\x00-\x1F\x7F\/\\\\]+/u', ' ', $filename) ?? '');
        $clean = $clean === '' ? 'bestand' : $clean;

        $fallback = Str::ascii($clean);
        $fallback = trim(preg_replace('/[^A-Za-z0-9._ ()-]+/', '_', $fallback) ?? '');
        $fallback = $fallback === '' ? 'bestand' : $fallback;

        return HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $clean, $fallback);
    }

    /**
     * Ordinary delete. On the versioned files bucket the old bytes stay
     * recoverable as a non-current version.
     *
     * @param  array<int, string>  $paths
     */
    public function delete(string $role, array $paths): void
    {
        $this->remove($role, $paths, definitive: false);
    }

    /**
     * Definitive delete, for takedowns: also removes every remote version.
     *
     * @param  array<int, string>  $paths
     */
    public function purge(string $role, array $paths): void
    {
        $this->remove($role, $paths, definitive: true);
    }

    /**
     * Serialise writers, the copier and the purger on one object.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function lock(string $role, string $path, Closure $callback): mixed
    {
        return Cache::lock(
            'fiche-storage:object:'.sha1($role.':'.$path),
            (int) config('fiche-storage.locks.object_seconds'),
        )->block((int) config('fiche-storage.locks.wait_seconds'), $callback);
    }

    /**
     * Stream a disk object through SHA-256 without keeping it in memory.
     */
    public function hashOf(FilesystemAdapter $disk, string $path): string
    {
        $stream = $disk->readStream($path);

        if (! is_resource($stream)) {
            throw new RuntimeException("Could not read {$path} for hashing.");
        }

        try {
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);

            return hash_final($context);
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function remove(string $role, array $paths, bool $definitive): void
    {
        $paths = array_values(array_unique(array_filter($paths)));

        if ($paths === []) {
            return;
        }

        $usesRemote = $this->mode()->usesRemote();
        $local = $this->localDisk($role);

        if ($usesRemote) {
            $this->register->markDeleting($role, $paths);
        }

        foreach ($paths as $path) {
            $this->lock($role, $path, function () use ($role, $path, $local, $usesRemote, $definitive) {
                $local->delete($path);

                if ($usesRemote) {
                    $remote = $this->remoteDisk($role);
                    $remote->delete($path);

                    if ($definitive) {
                        $this->versions->deleteAllVersions($remote, $path);
                    }
                }
            });

            $this->forgetExistence($role, $path);
        }

        if ($usesRemote) {
            $this->register->markDeleted($role, $paths);
        }

        $this->pruneEmptyLocalDirectories($local, $paths);
    }

    /**
     * Clean up per-file directories (files/media/4506, file-previews/4506) once
     * they run empty. Top-level directories are shared and never removed.
     *
     * @param  array<int, string>  $paths
     */
    private function pruneEmptyLocalDirectories(FilesystemAdapter $disk, array $paths): void
    {
        foreach (array_unique(array_map('dirname', $paths)) as $directory) {
            if (! str_contains($directory, '/') || ! $disk->directoryExists($directory)) {
                continue;
            }

            if ($disk->files($directory) === [] && $disk->directories($directory) === []) {
                $disk->deleteDirectory($directory);
            }
        }
    }

    private function remotePreviewExists(string $path): bool
    {
        $key = $this->existenceCacheKey(self::PREVIEWS, $path);
        $cached = Cache::get($key);

        if ($cached !== null) {
            return $cached;
        }

        try {
            $exists = $this->remoteDisk(self::PREVIEWS)->exists($path);
        } catch (Throwable $e) {
            report($e);

            return false;
        }

        Cache::put($key, $exists, $exists ? now()->addDay() : now()->addMinutes(5));

        return $exists;
    }

    private function forgetExistence(string $role, string $path): void
    {
        Cache::forget($this->existenceCacheKey($role, $path));
    }

    private function existenceCacheKey(string $role, string $path): string
    {
        return 'fiche-storage:exists:'.sha1($role.':'.$path);
    }

    private function workspaceName(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return 'source-'.Str::lower(Str::random(8)).($extension !== '' ? '.'.$extension : '');
    }
}
