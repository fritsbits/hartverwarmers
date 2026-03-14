<?php

namespace App\Console\Commands;

use App\Jobs\AssignFicheIcon;
use App\Models\Fiche;
use Illuminate\Console\Command;

class AssignFicheIcons extends Command
{
    protected $signature = 'fiches:assign-icons {--force : Re-assign icons even for fiches that already have one}';

    protected $description = 'Assign Lucide icons to fiches using AI';

    public function handle(): int
    {
        $query = Fiche::query();

        if (! $this->option('force')) {
            $query->whereNull('icon');
        }

        $fiches = $query->get();

        if ($fiches->isEmpty()) {
            $this->info('No fiches to process.');

            return self::SUCCESS;
        }

        $this->info("Processing {$fiches->count()} fiches...");
        $bar = $this->output->createProgressBar($fiches->count());
        $bar->start();

        foreach ($fiches as $fiche) {
            AssignFicheIcon::dispatch($fiche);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done! Icons will be assigned as jobs are processed.');

        return self::SUCCESS;
    }
}
