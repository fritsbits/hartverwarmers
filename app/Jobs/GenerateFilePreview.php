<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;

class GenerateFilePreview implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /** @var array<int, int> */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public int $fileId,
    ) {}

    public function handle(): void
    {
        Artisan::call('file:generate-previews', ['--file' => $this->fileId]);
    }
}
