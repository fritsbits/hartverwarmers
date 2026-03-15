<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CleanupMedia extends Command
{
    protected $signature = 'app:cleanup-media {--force : Actually delete orphaned folders}';

    protected $description = 'List or remove media folders not linked to any imported file';

    public function handle(): int
    {
        $force = $this->option('force');

        // Get all media IDs that are used by imported files
        $usedMediaIds = DB::table('files')
            ->whereNotNull('fiche_id')
            ->pluck('path')
            ->map(function ($path) {
                if (preg_match('#files/media/(\d+)/#', $path, $matches)) {
                    return $matches[1];
                }

                return null;
            })
            ->filter()
            ->flip();

        // List all folders in files/media/
        $allFolders = Storage::disk('public')->directories('files/media');
        $orphaned = [];

        foreach ($allFolders as $folder) {
            $mediaId = basename($folder);
            if (! $usedMediaIds->has($mediaId)) {
                $orphaned[] = $folder;
            }
        }

        $this->info('Total media folders: '.count($allFolders));
        $this->info("Used by imported files: {$usedMediaIds->count()}");
        $this->info('Orphaned: '.count($orphaned));

        if (empty($orphaned)) {
            $this->info('No orphaned folders found.');

            return self::SUCCESS;
        }

        if (! $force) {
            $this->warn('Dry run — use --force to actually delete. Showing first 20 orphaned:');
            foreach (array_slice($orphaned, 0, 20) as $folder) {
                $this->line("  {$folder}");
            }
            if (count($orphaned) > 20) {
                $this->line('  ... and '.(count($orphaned) - 20).' more');
            }

            return self::SUCCESS;
        }

        if (! $this->confirm('Delete '.count($orphaned).' orphaned media folders?')) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($orphaned as $folder) {
            Storage::disk('public')->deleteDirectory($folder);
            $deleted++;
        }

        $this->info("Deleted {$deleted} orphaned media folders.");

        return self::SUCCESS;
    }
}
