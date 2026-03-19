<?php

namespace App\Jobs;

use App\Models\File;
use App\Services\FileTextExtractor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ExtractFileText implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $fileId,
    ) {}

    public function handle(): void
    {
        $file = File::find($this->fileId);

        if (! $file || $file->extracted_text !== null || $file->source_file_id !== null) {
            return;
        }

        $extractor = app(FileTextExtractor::class);
        $storagePath = Storage::disk('public')->path($file->path);
        $text = $extractor->extract($storagePath, $file->mime_type);

        if ($text) {
            $file->update(['extracted_text' => $text]);
        }
    }
}
