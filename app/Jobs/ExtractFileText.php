<?php

namespace App\Jobs;

use App\Models\File;
use App\Services\FicheStorage\FicheStorage;
use App\Services\FileTextExtractor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExtractFileText implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $fileId,
    ) {}

    public function handle(FicheStorage $storage, FileTextExtractor $extractor): void
    {
        $file = File::find($this->fileId);

        if (! $file || $file->extracted_text !== null || $file->source_file_id !== null) {
            return;
        }

        $text = $storage->withLocalCopy(FicheStorage::FILES, $file->path, fn (string $path) => $extractor->extract($path, $file->mime_type));

        if ($text) {
            $file->update(['extracted_text' => $text]);
        }
    }
}
