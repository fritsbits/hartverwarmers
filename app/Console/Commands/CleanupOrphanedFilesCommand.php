<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Services\FichePurger;
use Illuminate\Console\Command;

class CleanupOrphanedFilesCommand extends Command
{
    protected $signature = 'file:cleanup-orphans';

    protected $description = 'Delete orphaned files (no fiche) older than 24 hours';

    public function handle(FichePurger $purger): int
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
            $purger->purgeFile($file);
            $deleted++;
        }

        $this->info("Deleted {$deleted} orphaned files.");

        return self::SUCCESS;
    }
}
