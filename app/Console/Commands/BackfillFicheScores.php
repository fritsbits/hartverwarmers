<?php

namespace App\Console\Commands;

use App\Jobs\AssessFicheQuality;
use App\Models\Fiche;
use Illuminate\Console\Command;

class BackfillFicheScores extends Command
{
    protected $signature = 'fiches:backfill-scores {--quality : Also dispatch AI quality assessment jobs}';

    protected $description = 'Backfill completeness scores for all published fiches, optionally queue AI quality assessments';

    public function handle(): int
    {
        $fiches = Fiche::query()->published()->get();
        $this->info("Processing {$fiches->count()} published fiches...");

        $bar = $this->output->createProgressBar($fiches->count());

        foreach ($fiches as $fiche) {
            $fiche->updateQuietly([
                'completeness_score' => $fiche->calculateCompletenessScore(),
            ]);

            if ($this->option('quality') && ! $fiche->quality_assessed_at) {
                AssessFicheQuality::dispatch($fiche);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done! Completeness scores updated.');

        if ($this->option('quality')) {
            $queued = Fiche::query()->published()->whereNull('quality_assessed_at')->count();
            $this->info("Queued {$queued} fiches for AI quality assessment.");
        }

        return self::SUCCESS;
    }
}
