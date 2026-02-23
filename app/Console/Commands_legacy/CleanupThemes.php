<?php

namespace App\Console\Commands;

use App\Models\Theme;
use Illuminate\Console\Command;

class CleanupThemes extends Command
{
    protected $signature = 'themes:cleanup
                            {--dry-run : Preview cleanup without deleting}';

    protected $description = 'Remove duplicate themes, keeping the one with the most recent start date';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info($dryRun ? 'DRY RUN - No changes will be saved' : 'Cleaning up duplicate themes...');

        $duplicateTitles = Theme::selectRaw('title, COUNT(*) as count')
            ->groupBy('title')
            ->having('count', '>', 1)
            ->pluck('title');

        if ($duplicateTitles->isEmpty()) {
            $this->info('No duplicate themes found.');

            return 0;
        }

        $this->info("Found {$duplicateTitles->count()} titles with duplicates:");

        $totalDeleted = 0;

        foreach ($duplicateTitles as $title) {
            $themes = Theme::where('title', $title)
                ->orderByDesc('start')
                ->get();

            $keep = $themes->first();
            $toDelete = $themes->slice(1);

            $this->line("  {$title}:");
            $this->line("    Keep: ID {$keep->id} ({$keep->start?->format('d/m/Y')})");

            foreach ($toDelete as $theme) {
                $this->line("    Delete: ID {$theme->id} ({$theme->start?->format('d/m/Y')})");

                if (! $dryRun) {
                    $theme->delete();
                }

                $totalDeleted++;
            }
        }

        $this->newLine();
        $this->info("Deleted {$totalDeleted} duplicate themes.");

        $remaining = Theme::count();
        $this->info("Remaining themes: {$remaining}");

        if ($dryRun) {
            $this->warn('This was a dry run. Run without --dry-run to delete duplicates.');
        }

        return 0;
    }
}
