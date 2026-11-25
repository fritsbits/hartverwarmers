<?php

namespace App\Services\FicheStorage;

use Aws\S3\Exception\S3Exception;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Throwable;

/**
 * Cheap read check per bucket: HEAD on a sentinel object that
 * files:storage-prepare wrote. Proves reachability and credentials, not
 * that uploads work; that is what the smoke test is for.
 */
class BucketHealth
{
    public function __construct(
        private FicheStorage $storage,
        private MigrationRegister $register,
    ) {}

    /**
     * @return array<string, array{ok: bool, error: ?string, at: int}>
     */
    public function check(): array
    {
        $results = [];

        foreach ([FicheStorage::FILES, FicheStorage::PREVIEWS] as $role) {
            $results[$role] = ['ok' => true, 'error' => null, 'at' => now()->timestamp];

            try {
                if (! $this->sentinelExists($role)) {
                    $results[$role] = ['ok' => false, 'error' => 'Sentinel ontbreekt; voer files:storage-prepare uit.', 'at' => now()->timestamp];
                }
            } catch (Throwable $e) {
                $results[$role] = ['ok' => false, 'error' => class_basename($e).': '.str($e->getMessage())->limit(200), 'at' => now()->timestamp];
            }
        }

        $this->register->putMeta('bucket_health', $results);

        return $results;
    }

    public function healthy(): bool
    {
        return collect($this->check())->every(fn (array $result) => $result['ok']);
    }

    public function writeSentinels(): void
    {
        foreach ([FicheStorage::FILES, FicheStorage::PREVIEWS] as $role) {
            $this->storage->remoteDisk($role)->put(config('fiche-storage.health.sentinel'), 'hartverwarmers '.now()->toIso8601String());
        }
    }

    private function sentinelExists(string $role): bool
    {
        $disk = $this->storage->remoteDisk($role);
        $sentinel = config('fiche-storage.health.sentinel');

        if (! $disk instanceof AwsS3V3Adapter) {
            return $disk->exists($sentinel);
        }

        $timeout = (int) config('fiche-storage.health.timeout_seconds');

        try {
            $disk->getClient()->headObject([
                'Bucket' => $disk->getConfig()['bucket'],
                'Key' => $disk->path($sentinel),
                '@http' => ['timeout' => $timeout, 'connect_timeout' => $timeout],
            ]);
        } catch (S3Exception $e) {
            if ($e->getStatusCode() === 404) {
                return false;
            }

            throw $e;
        }

        return true;
    }
}
