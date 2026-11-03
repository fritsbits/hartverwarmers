<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Services\FicheStorage\FicheStorage;
use App\Services\FicheStorage\ObjectMissing;
use App\Services\FileTextExtractor;
use Illuminate\Console\Command;

class ExtractFileTextCommand extends Command
{
    protected $signature = 'file:extract-text
        {--file= : Specific file ID}
        {--all : Process all files without extracted text}';

    protected $description = 'Extract text content from uploaded files (PDF, PPTX, DOCX)';

    public function __construct(
        private FileTextExtractor $extractor,
        private FicheStorage $storage,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('file')) {
            $file = File::find($this->option('file'));

            if (! $file) {
                $this->error('File not found.');

                return self::FAILURE;
            }

            if ($file->source_file_id !== null) {
                $this->warn('Skipping: this is a generated PDF, not a source file.');

                return self::SUCCESS;
            }

            return $this->processFile($file) ? self::SUCCESS : self::FAILURE;
        }

        if ($this->option('all')) {
            $files = File::whereNull('extracted_text')->whereNull('source_file_id')->get();
            $this->info("Processing {$files->count()} files...");

            $success = 0;
            foreach ($files as $file) {
                if ($this->processFile($file)) {
                    $success++;
                }
            }

            $this->info("Extracted text from {$success}/{$files->count()} files.");

            return self::SUCCESS;
        }

        $this->error('Please specify --file=ID or --all');

        return self::FAILURE;
    }

    private function processFile(File $file): bool
    {
        $this->info("Processing: {$file->original_filename} (ID: {$file->id})");

        $isImage = str_starts_with($file->mime_type, 'image/');

        if ($isImage) {
            $this->warn('  Skipping: images have no extractable text.');

            return false;
        }

        $isPdf = str_contains($file->mime_type, 'pdf');
        $isPptx = str_contains($file->mime_type, 'presentation') || str_contains($file->mime_type, 'powerpoint');
        $isDocx = str_contains($file->mime_type, 'word') || str_contains($file->mime_type, 'document');

        if (! $isPdf && ! $isPptx && ! $isDocx) {
            $this->warn("  Skipping: unsupported file type ({$file->mime_type})");

            return false;
        }

        try {
            $text = $this->storage->withLocalCopy(FicheStorage::FILES, $file->path, fn (string $path) => $this->extractor->extract($path, $file->mime_type));
        } catch (ObjectMissing) {
            $this->error("  File not found in storage: {$file->path}");

            return false;
        }

        if ($text === null) {
            $this->warn('  No text content found in file.');

            return false;
        }

        $file->update(['extracted_text' => $text]);
        $this->info('  Extracted '.str_word_count($text).' words.');

        return true;
    }
}
