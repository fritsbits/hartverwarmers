<?php

namespace App\Jobs;

use App\Services\FicheStorage\LocalCleanup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * One chunk of an approved local cleanup plan; schedules the next chunk.
 */
class CleanupLocalFicheObjects implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public function __construct(
        public string $runId,
    ) {}

    public function handle(LocalCleanup $cleanup): void
    {
        if ($cleanup->processChunk($this->runId)) {
            self::dispatch($this->runId)->delay(now()->addSeconds(5));
        }
    }
}
