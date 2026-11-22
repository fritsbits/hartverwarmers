<?php

namespace App\Services\Backups;

use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ObjectBackup
{
    public function __construct(private BackupSafety $safety) {}

    /** @return array{copied: int, reused: int, manifest: ?string} */
    public function copy(bool $dryRun = false): array
    {
        $destination = $this->safety->destination();
        $versioning = $destination->getClient()->getBucketVersioning([
            'Bucket' => $destination->getConfig()['bucket'],
        ]);

        if (! empty($versioning['Status'])) {
            throw new RuntimeException('De bestemmingsbucket heeft versioning; Hetzner ondersteunt daar geen conditionele uploads.');
        }

        $prefix = config('backups.objects.prefix');
        $startedAt = now()->utc()->toIso8601String();
        $snapshot = ['format' => 1, 'started_at' => $startedAt, 'sources' => []];
        $copied = $reused = 0;

        foreach (config('backups.objects.sources') as $sourceName) {
            $source = $this->source($sourceName);
            $dataPrefix = $prefix.'/data/'.$sourceName.'/';
            $existing = [];

            foreach ($this->objects($destination, $dataPrefix) as $object) {
                $existing[$object['Key']] = (int) $object['Size'];
            }

            $entries = [];

            foreach ($this->objects($source) as $object) {
                $size = (int) $object['Size'];
                $modified = $object['LastModified']->format(DATE_ATOM);
                $revision = hash('sha256', $object['ETag'].'|'.$size.'|'.$modified);
                $key = $dataPrefix.hash('sha256', $object['Key']).'/'.$revision;

                if (array_key_exists($key, $existing)) {
                    if ($existing[$key] !== $size) {
                        throw new RuntimeException('Een bestaande objectback-up heeft een afwijkende grootte; er wordt niets overschreven.');
                    }

                    $reused++;
                } else {
                    if (! $dryRun) {
                        $this->copyObject($source, $destination, $object, $key);
                    }

                    $copied++;
                }

                if (! $dryRun) {
                    $head = $destination->getClient()->headObject([
                        'Bucket' => $destination->getConfig()['bucket'], 'Key' => $key,
                    ]);
                    $checksum = $head['Metadata']['sha256'] ?? '';

                    if ((int) $head['ContentLength'] !== $size || ! preg_match('/^[a-f0-9]{64}$/', $checksum)) {
                        throw new RuntimeException('De objectback-up heeft geen geldige grootte of SHA-256-controlesom.');
                    }

                    $entries[] = [
                        'source_key' => $object['Key'], 'backup_key' => $key,
                        'size' => $size, 'etag' => $object['ETag'],
                        'last_modified' => $modified, 'sha256' => $checksum,
                        'content_type' => $head['ContentType'] ?? 'application/octet-stream',
                    ];
                }
            }

            $snapshot['sources'][] = [
                'disk' => $sourceName, 'bucket' => $source->getConfig()['bucket'],
                'objects' => $entries,
            ];
        }

        $manifest = null;

        if (! $dryRun) {
            $snapshot['completed_at'] = now()->utc()->toIso8601String();
            $manifest = $prefix.'/manifests/'.now()->utc()->format('Y-m-d\TH-i-s').'-'.Str::uuid().'.json';
            $body = json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $destination->getClient()->putObject([
                'Bucket' => $destination->getConfig()['bucket'], 'Key' => $manifest,
                'Body' => $body, 'ContentType' => 'application/json',
                'ContentMD5' => base64_encode(md5($body, true)),
                'ACL' => 'private', 'IfNoneMatch' => '*',
            ]);
        }

        return ['copied' => $copied, 'reused' => $reused, 'manifest' => $manifest];
    }

    public function assertFresh(): void
    {
        $destination = $this->safety->destination();
        $latest = null;

        foreach ($this->objects($destination, config('backups.objects.prefix').'/manifests/') as $object) {
            if (str_ends_with($object['Key'], '.json') && ($latest === null || strcmp($object['Key'], $latest['Key']) > 0)) {
                $latest = $object;
            }
        }

        if ($latest === null) {
            throw new RuntimeException('Er is nog geen volledig S3-herstelmanifest.');
        }

        $result = $destination->getClient()->getObject([
            'Bucket' => $destination->getConfig()['bucket'], 'Key' => $latest['Key'],
        ]);
        $snapshot = json_decode((string) $result['Body'], true, flags: JSON_THROW_ON_ERROR);
        $sources = array_column($snapshot['sources'] ?? [], 'disk');
        $expected = config('backups.objects.sources');
        sort($sources);
        sort($expected);

        if (($snapshot['format'] ?? null) !== 1 || $sources !== $expected || empty($snapshot['started_at']) || empty($snapshot['completed_at'])) {
            throw new RuntimeException('Het S3-herstelmanifest is onvolledig.');
        }

        if (strtotime($snapshot['started_at']) < now()->subHours(config('backups.objects.maximum_age_hours'))->timestamp) {
            throw new RuntimeException('De laatste volledige S3-kopie is ouder dan 36 uur.');
        }
    }

    private function source(string $name): AwsS3V3Adapter
    {
        foreach (['key', 'secret', 'bucket', 'endpoint', 'region'] as $field) {
            if (! config('filesystems.disks.'.$name.'.'.$field)) {
                throw new RuntimeException('Een S3-bron is onvolledig geconfigureerd.');
            }
        }

        $disk = Storage::disk($name);

        if (! $disk instanceof AwsS3V3Adapter || ! str_starts_with($disk->getConfig()['endpoint'], 'https://') || ! empty($disk->getConfig()['root'])) {
            throw new RuntimeException('S3-bronnen vereisen HTTPS en toegang tot de volledige bucket.');
        }

        return $disk;
    }

    /** @return iterable<array<string, mixed>> */
    private function objects(AwsS3V3Adapter $disk, string $prefix = ''): iterable
    {
        $pages = $disk->getClient()->getPaginator('ListObjectsV2', [
            'Bucket' => $disk->getConfig()['bucket'], 'Prefix' => $prefix,
        ]);

        foreach ($pages as $page) {
            foreach ($page['Contents'] ?? [] as $object) {
                yield $object;
            }
        }
    }

    /** @param array<string, mixed> $object */
    private function copyObject(AwsS3V3Adapter $source, AwsS3V3Adapter $destination, array $object, string $key): void
    {
        $size = (int) $object['Size'];

        if ($size > 5 * 1024 ** 3) {
            throw new RuntimeException('Een object is groter dan de 5 GiB-limiet van deze kopiemethode. Multipartondersteuning is nodig.');
        }

        $directory = $this->safety->temporaryDirectory($size);
        $temporary = tempnam($directory, 'object-');

        if ($temporary === false) {
            throw new RuntimeException('Een tijdelijk objectbestand kan niet worden aangemaakt.');
        }

        try {
            $download = $source->getClient()->getObject([
                'Bucket' => $source->getConfig()['bucket'], 'Key' => $object['Key'],
                'IfMatch' => $object['ETag'], 'SaveAs' => $temporary,
            ]);

            clearstatcache(true, $temporary);

            if (filesize($temporary) !== $size || $download['LastModified']->format(DATE_ATOM) !== $object['LastModified']->format(DATE_ATOM)) {
                throw new RuntimeException('Een bronobject is tijdens de kopie veranderd; het manifest wordt niet gepubliceerd.');
            }

            $destination->getClient()->putObject([
                'Bucket' => $destination->getConfig()['bucket'], 'Key' => $key,
                'SourceFile' => $temporary, 'ContentLength' => $size,
                'ContentType' => $download['ContentType'] ?? 'application/octet-stream',
                'ContentMD5' => base64_encode(hash_file('md5', $temporary, true)),
                'Metadata' => ['sha256' => hash_file('sha256', $temporary)],
                'ACL' => 'private', 'IfNoneMatch' => '*',
            ]);
        } finally {
            unlink($temporary);
        }
    }
}
