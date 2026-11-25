<?php

namespace Tests\Concerns;

use App\Services\FicheStorage\ObjectVersions;
use Illuminate\Filesystem\FilesystemAdapter;

/**
 * Records definitive purges instead of calling the S3 version API.
 */
class FakeObjectVersions extends ObjectVersions
{
    /** @var array<int, string> */
    public array $purged = [];

    public function deleteAllVersions(FilesystemAdapter $disk, string $path): int
    {
        $disk->delete($path);
        $this->purged[] = $path;

        return 1;
    }

    public function versionCount(FilesystemAdapter $disk, string $path): int
    {
        return $disk->exists($path) ? 1 : 0;
    }
}
