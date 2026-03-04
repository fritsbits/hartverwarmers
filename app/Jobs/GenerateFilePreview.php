<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;

class GenerateFilePreview implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $fileId,
    ) {}

    public function handle(): void
    {
        Artisan::call('file:generate-previews', ['--file' => $this->fileId]);
    }
}
