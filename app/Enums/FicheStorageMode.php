<?php

namespace App\Enums;

enum FicheStorageMode: string
{
    case Local = 'local';
    case Mirror = 'mirror';
    case S3Fallback = 's3-fallback';
    case S3 = 's3';

    /**
     * Which locations are read, in order of preference.
     *
     * @return array<int, 'local'|'remote'>
     */
    public function readOrder(): array
    {
        return match ($this) {
            self::Local => ['local'],
            self::Mirror => ['local', 'remote'],
            self::S3Fallback => ['remote', 'local'],
            self::S3 => ['remote'],
        };
    }

    /**
     * Which locations every write must reach before it may be published.
     *
     * @return array<int, 'local'|'remote'>
     */
    public function writeTargets(): array
    {
        return match ($this) {
            self::Local => ['local'],
            self::Mirror => ['local', 'remote'],
            self::S3Fallback, self::S3 => ['remote'],
        };
    }

    public function usesRemote(): bool
    {
        return $this !== self::Local;
    }

    /**
     * Remote is the source of truth: new writes no longer land locally.
     */
    public function remoteIsAuthoritative(): bool
    {
        return $this === self::S3Fallback || $this === self::S3;
    }
}
