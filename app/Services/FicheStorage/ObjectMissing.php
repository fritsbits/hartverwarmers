<?php

namespace App\Services\FicheStorage;

use RuntimeException;

/**
 * The object exists on none of the disks the current mode reads from.
 * Unreachable buckets and denied access surface as their own exceptions.
 */
class ObjectMissing extends RuntimeException
{
    public static function at(string $role, string $path): self
    {
        return new self("Fiche storage object not found: {$role}:{$path}");
    }
}
