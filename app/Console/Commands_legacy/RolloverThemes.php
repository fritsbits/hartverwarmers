<?php

namespace App\Console\Commands;

use App\Models\Theme;
use Illuminate\Console\Command;

class RolloverThemes extends Command
{
    protected $signature = 'themes:rollover
                            {--dry-run : Preview rollover without saving}';

    protected $description = 'Roll over fixed-date themes to next year when past';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info($dryRun ? 'DRY RUN - No changes will be saved' : 'Rolling over themes...');

        $themes = Theme::needsRollover()->get();

        if ($themes->isEmpty()) {
            $this->info('No themes need rollover.');

            return 0;
        }

        $this->info("Found {$themes->count()} themes to roll over:");

        foreach ($themes as $theme) {
            $oldDate = $theme->start->format('d/m/Y');
            $newDate = $theme->start->copy()->addYear()->format('d/m/Y');

            $this->line("  {$theme->title}: {$oldDate} -> {$newDate}");

            if (! $dryRun) {
                $theme->rolloverToNextYear();
            }
        }

        $this->newLine();
        $this->info("Rolled over {$themes->count()} themes.");

        if ($dryRun) {
            $this->warn('This was a dry run. Run without --dry-run to save changes.');
        }

        return 0;
    }
}
