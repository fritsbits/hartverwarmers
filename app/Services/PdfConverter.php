<?php

namespace App\Services;

use App\Models\File;
use App\Services\FicheStorage\FicheStorage;
use App\Services\FicheStorage\ObjectMissing;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File as LocalFiles;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class PdfConverter
{
    /**
     * Seconds soffice may run. The lock outlives it, and lock wait plus run
     * time stay well under the 300 s job timeout.
     */
    private const PROCESS_TIMEOUT = 150;

    private const LOCK_WAIT = 45;

    public function __construct(
        private FicheStorage $storage,
    ) {}

    /**
     * Convert a document to PDF via LibreOffice soffice, into $outputDir.
     *
     * All callers share one lock, so only one soffice runs at a time, and
     * each run gets its own profile directory inside $outputDir.
     *
     * @return string|null Path of the PDF inside $outputDir, or null on failure
     */
    public function convert(string $sourcePath, string $outputDir): ?string
    {
        return Cache::lock('soffice-conversion', self::PROCESS_TIMEOUT + 30)->block(self::LOCK_WAIT, function () use ($sourcePath, $outputDir) {
            $profile = $outputDir.'/.soffice-profile-'.Str::lower(Str::random(8));

            try {
                $result = Process::timeout(self::PROCESS_TIMEOUT)->run([
                    self::sofficeBinary(),
                    '-env:UserInstallation=file://'.$profile,
                    '--headless',
                    '--convert-to', 'pdf',
                    '--outdir', $outputDir,
                    $sourcePath,
                ]);
            } catch (ProcessTimedOutException) {
                Log::warning('PdfConverter: LibreOffice conversion timed out', ['source' => $sourcePath]);

                return null;
            } finally {
                LocalFiles::deleteDirectory($profile);
            }

            if (! $result->successful()) {
                Log::warning('PdfConverter: LibreOffice conversion failed', [
                    'source' => $sourcePath,
                    'output' => $result->output().$result->errorOutput(),
                ]);

                return null;
            }

            $pdfPath = $outputDir.'/'.pathinfo($sourcePath, PATHINFO_FILENAME).'.pdf';

            if (! file_exists($pdfPath)) {
                Log::warning('PdfConverter: PDF output not found', ['expected' => $pdfPath]);

                return null;
            }

            return $pdfPath;
        });
    }

    /**
     * Resolve the soffice binary path, checking common locations.
     *
     * Queue workers often run with a minimal PATH that excludes
     * Homebrew's /opt/homebrew/bin, so bare `soffice` fails.
     */
    public static function sofficeBinary(): string
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
     *
     * Idempotent: a retry or a second caller gets the existing PDF version
     * instead of a duplicate. A failed conversion returns null; storage
     * errors propagate so the calling job can retry.
     */
    public function convertAndStore(File $sourceFile): ?File
    {
        return Cache::lock("fiche-file:{$sourceFile->id}:pdf", 600)->block(60, function () use ($sourceFile) {
            $existing = File::query()->where('source_file_id', $sourceFile->id)->first();

            if ($existing) {
                return $existing;
            }

            try {
                return $this->storage->withLocalCopy(FicheStorage::FILES, $sourceFile->path, fn (string $sourcePath) => $this->storage->withWorkspace(function (string $workspace) use ($sourceFile, $sourcePath) {
                    $pdfPath = $this->convert($sourcePath, $workspace);

                    if (! $pdfPath) {
                        return null;
                    }

                    $storagePath = 'files/'.Str::uuid().'.pdf';
                    $this->storage->putFile(FicheStorage::FILES, $storagePath, $pdfPath);

                    return File::create([
                        'fiche_id' => $sourceFile->fiche_id,
                        'source_file_id' => $sourceFile->id,
                        'original_filename' => pathinfo($sourceFile->original_filename, PATHINFO_FILENAME).'.pdf',
                        'path' => $storagePath,
                        'mime_type' => 'application/pdf',
                        'size_bytes' => filesize($pdfPath),
                        'sort_order' => $sourceFile->sort_order + 1,
                    ]);
                }));
            } catch (ObjectMissing $e) {
                Log::warning('PdfConverter: source file missing', ['file_id' => $sourceFile->id, 'error' => $e->getMessage()]);

                return null;
            }
        });
    }
}
