<?php

namespace App\Console\Commands;

use App\Models\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanedFilesCommand extends Command
{
    protected $signature = 'file:cleanup-orphans';

    protected $description = 'Delete orphaned files (no fiche) older than 24 hours';

    public function handle(): int
    {
        $orphans = File::query()
            ->whereNull('fiche_id')
            ->where('created_at', '<', now()->subDay())
            ->get();

        if ($orphans->isEmpty()) {
            $this->info('No orphaned files found.');

            return self::SUCCESS;
        }

        $this->info("Found {$orphans->count()} orphaned files.");

        $deleted = 0;
        foreach ($orphans as $file) {
            Storage::disk('public')->delete($file->path);

            if ($file->preview_images) {
                foreach ($file->preview_images as $preview) {
                    Storage::disk('public')->delete($preview);
                }
            }

            $file->delete();
            $deleted++;
        }

        $this->info("Deleted {$deleted} orphaned files.");

        return self::SUCCESS;
    }
}
