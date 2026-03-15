<?php

namespace App\Console\Commands;

use App\Models\Fiche;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class GenerateFicheZips extends Command
{
    protected $signature = 'app:generate-zips
                            {--force : Regenerate existing ZIPs}
                            {--fiche= : Process a specific fiche ID}';

    protected $description = 'Pre-generate ZIP archives for fiches with multiple downloadable files';

    public function handle(): int
    {
        $query = Fiche::whereHas('files', function ($q) {
            $q->whereNull('source_file_id'); // Only original files, not generated PDFs
        })->with(['files' => function ($q) {
            $q->whereNull('source_file_id')->orderBy('sort_order');
        }]);

        if ($ficheId = $this->option('fiche')) {
            $query->where('id', $ficheId);
        }

        $fiches = $query->get();

        $generated = 0;
        $skipped = 0;
        $singleFile = 0;

        foreach ($fiches as $fiche) {
            $downloadableFiles = $fiche->files;

            // No ZIP needed for single file
            if ($downloadableFiles->count() <= 1) {
                $singleFile++;

                // Clear stale zip_path if files were removed
                if ($fiche->zip_path) {
                    $this->deleteOldZip($fiche->zip_path);
                    $fiche->update(['zip_path' => null]);
                }

                continue;
            }

            // Skip if ZIP already exists (unless --force)
            if ($fiche->zip_path && ! $this->option('force')) {
                if (Storage::disk('public')->exists($fiche->zip_path)) {
                    $skipped++;

                    continue;
                }
            }

            // Delete old ZIP if regenerating
            if ($fiche->zip_path) {
                $this->deleteOldZip($fiche->zip_path);
            }

            // Create ZIP
            $zipRelativePath = "fiche-zips/{$fiche->slug}.zip";
            $zipAbsolutePath = Storage::disk('public')->path($zipRelativePath);

            // Ensure directory exists
            Storage::disk('public')->makeDirectory('fiche-zips');

            $zip = new ZipArchive;
            if ($zip->open($zipAbsolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                $this->warn("Failed to create ZIP for fiche {$fiche->id}: {$fiche->title}");

                continue;
            }

            $hasFiles = false;
            foreach ($downloadableFiles as $file) {
                $filePath = Storage::disk('public')->path($file->path);
                if (file_exists($filePath)) {
                    // Store without compression (files are already compressed)
                    $zip->addFile($filePath, $file->original_filename);
                    $zip->setCompressionName($file->original_filename, ZipArchive::CM_STORE);
                    $hasFiles = true;
                } else {
                    $this->warn("  Missing file: {$file->path}");
                }
            }

            $zip->close();

            if ($hasFiles) {
                $fiche->update(['zip_path' => $zipRelativePath]);
                $generated++;
                $this->line("  ZIP: {$fiche->title} ({$downloadableFiles->count()} files)");
            } else {
                // Clean up empty ZIP
                Storage::disk('public')->delete($zipRelativePath);
            }
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['ZIPs generated', $generated],
            ['Already exists (skipped)', $skipped],
            ['Single file (no ZIP)', $singleFile],
        ]);

        return self::SUCCESS;
    }

    private function deleteOldZip(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
