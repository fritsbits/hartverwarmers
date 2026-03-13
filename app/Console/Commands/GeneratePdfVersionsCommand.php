<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Services\PdfConverter;
use Illuminate\Console\Command;

class GeneratePdfVersionsCommand extends Command
{
    protected $signature = 'file:generate-pdf-versions
        {--file= : Specific file ID to convert}';

    protected $description = 'Generate PDF versions for PPTX/DOCX files that don\'t have one yet';

    public function handle(PdfConverter $converter): int
    {
        if ($fileId = $this->option('file')) {
            $file = File::find($fileId);

            if (! $file) {
                $this->error('File not found.');

                return self::FAILURE;
            }

            if (! $file->isConvertibleToPdf()) {
                $this->error("File {$file->original_filename} is not a convertible type.");

                return self::FAILURE;
            }

            return $this->convertFile($file, $converter) ? self::SUCCESS : self::FAILURE;
        }

        $files = File::whereNull('source_file_id')
            ->whereDoesntHave('pdfVersion')
            ->where(function ($q) {
                $q->where('mime_type', 'like', '%presentation%')
                    ->orWhere('mime_type', 'like', '%powerpoint%')
                    ->orWhere('mime_type', 'like', '%word%')
                    ->orWhere('mime_type', 'like', '%document%');
            })
            ->get();

        if ($files->isEmpty()) {
            $this->info('No files need PDF conversion.');

            return self::SUCCESS;
        }

        $this->info("Found {$files->count()} files to convert...");

        $success = 0;
        foreach ($files as $file) {
            if ($this->convertFile($file, $converter)) {
                $success++;
            }
        }

        $this->info("Generated PDF versions for {$success}/{$files->count()} files.");

        return self::SUCCESS;
    }

    private function convertFile(File $file, PdfConverter $converter): bool
    {
        $this->info("Converting: {$file->original_filename} (ID: {$file->id})");

        $pdfFile = $converter->convertAndStore($file);

        if (! $pdfFile) {
            $this->error("  Failed to convert {$file->original_filename}");

            return false;
        }

        $this->info("  Created PDF: {$pdfFile->original_filename} (ID: {$pdfFile->id})");

        return true;
    }
}
