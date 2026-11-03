<?php

namespace App\Services\FicheStorage;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Durable state of the fiche storage migration: one row per object with its
 * checksums and verification state, the copy/restore/cleanup runs, and a
 * small key/value store for mode history and smoke test results.
 *
 * Lives in its own SQLite file under storage/app/private, so the curated
 * application database needs no schema change and the state survives
 * releases and worker restarts. Timestamps are unix seconds.
 */
class MigrationRegister
{
    public const STATE_PENDING = 'pending';

    public const STATE_VERIFIED = 'verified';

    public const STATE_MISSING = 'missing';

    public const STATE_FAILED = 'failed';

    public const STATE_UNREFERENCED = 'unreferenced';

    public const STATE_DELETING = 'deleting';

    public const STATE_DELETED = 'deleted';

    private bool $schemaReady = false;

    public function connection(): ConnectionInterface
    {
        $name = config('fiche-storage.migration.connection');

        if (! $this->schemaReady) {
            $database = config("database.connections.{$name}.database");

            if ($database !== ':memory:' && ! file_exists($database)) {
                if (! is_dir(dirname($database))) {
                    mkdir(dirname($database), 0750, true);
                }
                touch($database);
                chmod($database, 0640);
            }

            $this->createSchema(DB::connection($name));
            $this->schemaReady = true;
        }

        return DB::connection($name);
    }

    public function objects(): Builder
    {
        return $this->connection()->table('objects');
    }

    public function runs(): Builder
    {
        return $this->connection()->table('runs');
    }

    public function cleanupItems(): Builder
    {
        return $this->connection()->table('cleanup_items');
    }

    /**
     * @return object{role: string, path: string, state: string, size: ?int, local_sha256: ?string, local_mtime: ?int, expected_sha256: ?string, remote_sha256: ?string, verified_at: ?int, local_state: ?string, local_verified_at: ?int, attempts: int, error: ?string, queued_at: ?int, seen_run: ?string, seen_round: ?int, written_at: ?int, delete_requested_at: ?int, deleted_at: ?int, fallback_hit_at: ?int, updated_at: int}|null
     */
    public function object(string $role, string $path): ?object
    {
        return $this->objects()->where('role', $role)->where('path', $path)->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function upsertObject(string $role, string $path, array $attributes): void
    {
        $attributes['updated_at'] = now()->timestamp;

        $this->connection()->transaction(function () use ($role, $path, $attributes) {
            $updated = $this->objects()->where('role', $role)->where('path', $path)->update($attributes);

            if ($updated === 0) {
                $this->objects()->insert(array_merge(
                    ['role' => $role, 'path' => $path, 'state' => self::STATE_PENDING, 'attempts' => 0],
                    $attributes,
                ));
            }
        });
    }

    /**
     * A writer stored new bytes remotely. Any earlier verification of this
     * object is void until a job has compared the remote bytes with $sha256.
     */
    public function recordWrite(string $role, string $path, string $sha256, int $size): void
    {
        $this->upsertObject($role, $path, [
            'state' => self::STATE_PENDING,
            'expected_sha256' => $sha256,
            'size' => $size,
            'written_at' => now()->timestamp,
            'verified_at' => null,
            'local_state' => null,
            'local_verified_at' => null,
            'attempts' => 0,
            'error' => null,
            'queued_at' => null,
            'delete_requested_at' => null,
            'deleted_at' => null,
        ]);
    }

    /**
     * Record the intent to delete before any bytes disappear, so a delayed
     * copy job never uploads the object again.
     *
     * @param  array<int, string>  $paths
     */
    public function markDeleting(string $role, array $paths): void
    {
        foreach ($paths as $path) {
            $this->upsertObject($role, $path, [
                'state' => self::STATE_DELETING,
                'delete_requested_at' => now()->timestamp,
            ]);
        }
    }

    /**
     * @param  array<int, string>  $paths
     */
    public function markDeleted(string $role, array $paths): void
    {
        foreach ($paths as $path) {
            $this->upsertObject($role, $path, [
                'state' => self::STATE_DELETED,
                'deleted_at' => now()->timestamp,
            ]);
        }
    }

    public function isDeleted(string $role, string $path): bool
    {
        $object = $this->object($role, $path);

        return $object !== null && in_array($object->state, [self::STATE_DELETING, self::STATE_DELETED], true);
    }

    public function recordFallbackHit(string $role, string $path): void
    {
        $this->upsertObject($role, $path, ['fallback_hit_at' => now()->timestamp]);
    }

    /**
     * Objects that must be verified remotely before remote may be trusted:
     * still referenced and not deleted, but not (yet) verified.
     */
    public function unverifiedCount(): int
    {
        return $this->objects()
            ->whereIn('state', [self::STATE_PENDING, self::STATE_MISSING, self::STATE_FAILED])
            ->count();
    }

    /**
     * Fallback reads that happened after the object was last verified.
     */
    public function unresolvedFallbackHits(): int
    {
        return $this->objects()
            ->whereNotNull('fallback_hit_at')
            ->whereNotIn('state', [self::STATE_DELETED, self::STATE_DELETING, self::STATE_UNREFERENCED])
            ->where(fn (Builder $query) => $query->whereNull('verified_at')->orWhereColumn('fallback_hit_at', '>', 'verified_at'))
            ->count();
    }

    public function recordError(string $message, ?string $role = null, ?string $path = null): void
    {
        $this->putMeta('last_error', [
            'message' => Str::limit($message, 500),
            'role' => $role,
            'path' => $path,
            'at' => now()->timestamp,
        ]);
    }

    public function createRun(string $kind, array $attributes = []): string
    {
        $id = now()->format('Ymd-His').'-'.Str::lower(Str::random(4));

        $this->runs()->insert(array_merge([
            'id' => $id,
            'kind' => $kind,
            'status' => 'running',
            'phase' => 'inventory',
            'round' => 1,
            'cursor' => 0,
            'started_at' => now()->timestamp,
            'round_started_at' => now()->timestamp,
            'updated_at' => now()->timestamp,
        ], $attributes));

        return $id;
    }

    /**
     * @return object{id: string, kind: string, status: string, phase: string, round: int, cursor: int, started_at: int, round_started_at: int, updated_at: int, finished_at: ?int, last_error: ?string, summary: ?string}|null
     */
    public function run(string $id): ?object
    {
        return $this->runs()->where('id', $id)->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateRun(string $id, array $attributes): void
    {
        $this->runs()->where('id', $id)->update(array_merge($attributes, ['updated_at' => now()->timestamp]));
    }

    public function activeRun(string $kind): ?object
    {
        return $this->runs()->where('kind', $kind)->whereIn('status', ['running', 'waiting'])->orderByDesc('rowid')->first();
    }

    public function latestRun(string $kind): ?object
    {
        return $this->runs()->where('kind', $kind)->orderByDesc('rowid')->first();
    }

    public function meta(string $key, mixed $default = null): mixed
    {
        $value = $this->connection()->table('meta')->where('key', $key)->value('value');

        return $value === null ? $default : json_decode($value, true);
    }

    public function putMeta(string $key, mixed $value): void
    {
        $this->connection()->table('meta')->upsert(
            [['key' => $key, 'value' => json_encode($value), 'updated_at' => now()->timestamp]],
            ['key'],
            ['value', 'updated_at'],
        );
    }

    private function createSchema(ConnectionInterface $connection): void
    {
        $connection->statement('CREATE TABLE IF NOT EXISTS objects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            role TEXT NOT NULL,
            path TEXT NOT NULL,
            state TEXT NOT NULL,
            size INTEGER NULL,
            local_sha256 TEXT NULL,
            local_mtime INTEGER NULL,
            expected_sha256 TEXT NULL,
            remote_sha256 TEXT NULL,
            verified_at INTEGER NULL,
            local_state TEXT NULL,
            local_verified_at INTEGER NULL,
            attempts INTEGER NOT NULL DEFAULT 0,
            error TEXT NULL,
            queued_at INTEGER NULL,
            seen_run TEXT NULL,
            seen_round INTEGER NULL,
            written_at INTEGER NULL,
            delete_requested_at INTEGER NULL,
            deleted_at INTEGER NULL,
            fallback_hit_at INTEGER NULL,
            updated_at INTEGER NOT NULL
        )');
        $connection->statement('CREATE UNIQUE INDEX IF NOT EXISTS objects_role_path ON objects (role, path)');
        $connection->statement('CREATE INDEX IF NOT EXISTS objects_state ON objects (state)');
        $connection->statement('CREATE INDEX IF NOT EXISTS objects_seen ON objects (seen_run, seen_round)');

        $connection->statement('CREATE TABLE IF NOT EXISTS runs (
            id TEXT PRIMARY KEY,
            kind TEXT NOT NULL,
            status TEXT NOT NULL,
            phase TEXT NOT NULL,
            round INTEGER NOT NULL DEFAULT 1,
            cursor INTEGER NOT NULL DEFAULT 0,
            started_at INTEGER NOT NULL,
            round_started_at INTEGER NOT NULL,
            updated_at INTEGER NOT NULL,
            finished_at INTEGER NULL,
            last_error TEXT NULL,
            summary TEXT NULL
        )');

        $connection->statement('CREATE TABLE IF NOT EXISTS cleanup_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            run_id TEXT NOT NULL,
            role TEXT NOT NULL,
            path TEXT NOT NULL,
            bytes INTEGER NOT NULL DEFAULT 0,
            state TEXT NOT NULL,
            reason TEXT NULL
        )');
        $connection->statement('CREATE INDEX IF NOT EXISTS cleanup_items_run ON cleanup_items (run_id, state)');

        $connection->statement('CREATE TABLE IF NOT EXISTS meta (
            key TEXT PRIMARY KEY,
            value TEXT NULL,
            updated_at INTEGER NOT NULL
        )');
    }
}
