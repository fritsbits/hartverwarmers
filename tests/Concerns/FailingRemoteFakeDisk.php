<?php

namespace Tests\Concerns;

use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToWriteFile;

/**
 * Remote fake bucket that refuses selected operations, to prove callers
 * never publish or forget anything when the bucket is unreachable.
 */
class FailingRemoteFakeDisk extends RemoteFakeDisk
{
    /** @var array<int, string> */
    public array $failing = [];

    public function writeStream($path, $resource, array $options = [])
    {
        if (in_array('writeStream', $this->failing, true)) {
            throw UnableToWriteFile::atLocation($path, 'Bucket unreachable.');
        }

        return parent::writeStream($path, $resource, $options);
    }

    public function delete($paths)
    {
        if (in_array('delete', $this->failing, true)) {
            throw UnableToDeleteFile::atLocation(is_array($paths) ? implode(', ', $paths) : $paths, 'Bucket unreachable.');
        }

        return parent::delete($paths);
    }
}
