<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfConverter
{
    /**
     * Convert a document to PDF via LibreOffice soffice.
     *
     * @return string|null Path to the temporary PDF file, or null on failure
     */
    public function convert(string $sourcePath): ?string
    {
        $outputDir = sys_get_temp_dir();
        $basename = pathinfo($sourcePath, PATHINFO_FILENAME);

        $command = sprintf(
            '%s --headless --convert-to pdf --outdir %s %s 2>&1',
            escapeshellarg($this->resolveSofficeBinary()),
            escapeshellarg($outputDir),
            escapeshellarg($sourcePath)
        );

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            Log::warning('PdfConverter: LibreOffice conversion failed', [
                'source' => $sourcePath,
                'output' => implode("\n", $output),
            ]);

            return null;
        }

        $pdfPath = $outputDir.'/'.$basename.'.pdf';

        if (! file_exists($pdfPath)) {
            Log::warning('PdfConverter: PDF output not found', ['expected' => $pdfPath]);

            return null;
        }

        return $pdfPath;
    }

    /**
     * Resolve the soffice binary path, checking common locations.
     *
     * Queue workers often run with a minimal PATH that excludes
     * Homebrew's /opt/homebrew/bin, so bare `soffice` fails.
     */
    private function resolveSofficeBinary(): string
    {
        $candidates = [
            '/opt/homebrew/bin/soffice',   // macOS (Apple Silicon Homebrew)
            '/usr/local/bin/soffice',      // macOS (Intel Homebrew) / Linux
            '/usr/bin/soffice',            // Linux (apt/yum)
        ];

        foreach ($candidates as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        return 'soffice';
    }

    /**
     * Convert a source file to PDF, store it, and create a File record.
     */
    public function convertAndStore(File $sourceFile): ?File
    {
        $lock = Cache::lock('soffice-conversion', 120);

        try {
            $lock->block(120);

            $sourcePath = Storage::disk('public')->path($sourceFile->path);
            $pdfPath = $this->convert($sourcePath);

            if (! $pdfPath) {
                return null;
            }

            $storageName = Str::uuid().'.pdf';
            $storagePath = 'files/'.$storageName;

            Storage::disk('public')->put($storagePath, file_get_contents($pdfPath));
            @unlink($pdfPath);

            $originalBasename = pathinfo($sourceFile->original_filename, PATHINFO_FILENAME);

            return File::create([
                'fiche_id' => $sourceFile->fiche_id,
                'source_file_id' => $sourceFile->id,
                'original_filename' => $originalBasename.'.pdf',
                'path' => $storagePath,
                'mime_type' => 'application/pdf',
                'size_bytes' => Storage::disk('public')->size($storagePath),
                'sort_order' => $sourceFile->sort_order + 1,
            ]);
        } catch (\Throwable $e) {
            Log::warning('PdfConverter: convertAndStore failed', [
                'file_id' => $sourceFile->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        } finally {
            $lock->release();
        }
    }
}
