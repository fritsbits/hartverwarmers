<?php

namespace App\Jobs;

use App\Services\FicheStorage\StorageMigration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * One bounded step of a fiche storage run; schedules the next step itself.
 */
class AdvanceFicheStorageRun implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public function __construct(
        public string $runId,
    ) {}

    public function handle(StorageMigration $migration): void
    {
        $migration->advance($this->runId);
    }
}
