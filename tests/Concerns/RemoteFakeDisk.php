<?php

namespace Tests\Concerns;

use DateTimeInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use LogicException;

/**
 * Stand-in for an S3 bucket in tests. Stores bytes in its own local root
 * (independent from the local fiche disks) but behaves like a remote disk:
 * there is no local path, so code that calls path() on it fails loudly
 * instead of passing by accident.
 */
class RemoteFakeDisk extends FilesystemAdapter
{
    public function path($path): string
    {
        throw new LogicException("Remote disk has no local path for {$path}.");
    }

    public function url($path): string
    {
        return 'https://'.$this->config['bucket'].'.objects.test/'.ltrim($path, '/');
    }

    public function providesTemporaryUrls(): bool
    {
        return true;
    }

    public function temporaryUrl($path, $expiration, array $options = []): string
    {
        return $this->url($path).'?'.http_build_query(array_merge($options, [
            'X-Amz-Expires' => $expiration instanceof DateTimeInterface ? $expiration->getTimestamp() - now()->timestamp : null,
            'X-Amz-Signature' => 'test',
        ]));
    }
}
