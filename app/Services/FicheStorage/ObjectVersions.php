<?php

namespace App\Services\FicheStorage;

use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Filesystem\FilesystemAdapter;
use LogicException;

/**
 * Version-level operations on a versioned bucket. A plain Flysystem delete
 * only adds a delete marker; a definitive purge removes every version.
 */
class ObjectVersions
{
    public function deleteAllVersions(FilesystemAdapter $disk, string $path): int
    {
        $disk = $this->s3($disk);
        $client = $disk->getClient();
        $bucket = $disk->getConfig()['bucket'];
        $key = $disk->path($path);
        $deleted = 0;

        $parameters = ['Bucket' => $bucket, 'Prefix' => $key];

        do {
            $result = $client->listObjectVersions($parameters);

            $targets = [];
            foreach (array_merge($result['Versions'] ?? [], $result['DeleteMarkers'] ?? []) as $version) {
                if ($version['Key'] === $key) {
                    $targets[] = ['Key' => $key, 'VersionId' => $version['VersionId']];
                }
            }

            if ($targets !== []) {
                $response = $client->deleteObjects(['Bucket' => $bucket, 'Delete' => ['Objects' => $targets, 'Quiet' => true]]);

                if (! empty($response['Errors'])) {
                    throw new \RuntimeException('Could not delete all versions of '.$key.': '.json_encode($response['Errors']));
                }

                $deleted += count($targets);
            }

            $parameters['KeyMarker'] = $result['NextKeyMarker'] ?? null;
            $parameters['VersionIdMarker'] = $result['NextVersionIdMarker'] ?? null;
        } while ($result['IsTruncated'] ?? false);

        return $deleted;
    }

    public function versionCount(FilesystemAdapter $disk, string $path): int
    {
        $disk = $this->s3($disk);
        $key = $disk->path($path);
        $result = $disk->getClient()->listObjectVersions(['Bucket' => $disk->getConfig()['bucket'], 'Prefix' => $key]);

        return collect($result['Versions'] ?? [])->where('Key', $key)->count();
    }

    private function s3(FilesystemAdapter $disk): AwsS3V3Adapter
    {
        if (! $disk instanceof AwsS3V3Adapter) {
            throw new LogicException('Object versions only exist on an S3 disk.');
        }

        return $disk;
    }
}
