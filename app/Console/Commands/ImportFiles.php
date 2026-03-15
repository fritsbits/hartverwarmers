<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportFiles extends Command
{
    protected $signature = 'app:import-files';

    protected $description = 'Import file records from old Soulcenter media table';

    public function handle(): int
    {
        $ficheMap = DB::table('fiches')
            ->whereNotNull('migration_id')
            ->pluck('id', 'migration_id');

        // Load old media records for published activities
        $mediaRecords = DB::connection('soulcenter')
            ->table('media')
            ->where('collection_name', 'downloads')
            ->where('model_type', 'like', '%Activity%')
            ->whereIn('model_id', $ficheMap->keys())
            ->select('id', 'model_id', 'file_name', 'mime_type', 'size', 'order_column', 'created_at')
            ->orderBy('model_id')
            ->orderBy('order_column')
            ->get();

        $this->info("Found {$mediaRecords->count()} media records to import");

        $imported = 0;
        $skippedNoFiche = 0;
        $skippedMissing = 0;
        $skippedExisting = 0;

        // Get existing file paths to avoid duplicates
        $existingPaths = DB::table('files')->pluck('path')->flip();

        DB::transaction(function () use ($mediaRecords, $ficheMap, $existingPaths, &$imported, &$skippedNoFiche, &$skippedMissing, &$skippedExisting) {
            foreach ($mediaRecords as $media) {
                $ficheId = $ficheMap[$media->model_id] ?? null;
                if (! $ficheId) {
                    $skippedNoFiche++;

                    continue;
                }

                $path = "files/media/{$media->id}/{$media->file_name}";

                // Skip if already imported
                if ($existingPaths->has($path)) {
                    $skippedExisting++;

                    continue;
                }

                // Verify file exists on disk
                if (! Storage::disk('public')->exists($path)) {
                    $this->warn("Missing file: {$path}");
                    $skippedMissing++;

                    continue;
                }

                DB::table('files')->insert([
                    'fiche_id' => $ficheId,
                    'original_filename' => $media->file_name,
                    'path' => $path,
                    'mime_type' => $media->mime_type,
                    'size_bytes' => $media->size,
                    'sort_order' => $media->order_column ?? 0,
                    'created_at' => $media->created_at ?? now(),
                    'updated_at' => $media->created_at ?? now(),
                ]);

                $existingPaths[$path] = true;
                $imported++;
            }
        });

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Imported', $imported],
            ['Skipped (no matching fiche)', $skippedNoFiche],
            ['Skipped (file missing on disk)', $skippedMissing],
            ['Skipped (already imported)', $skippedExisting],
        ]);

        return self::SUCCESS;
    }
}
