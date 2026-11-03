<?php

namespace App\Jobs;

use App\Services\FicheStorage\StorageSmokeTest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Live bucket test started by files:storage-prepare; reports to /admin/gezondheid.
 */
class RunStorageSmokeTest implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function handle(StorageSmokeTest $smokeTest): void
    {
        $smokeTest->run();
    }
}
