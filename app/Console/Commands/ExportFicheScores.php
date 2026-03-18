<?php

namespace App\Console\Commands;

use App\Models\Fiche;
use Illuminate\Console\Command;

class ExportFicheScores extends Command
{
    protected $signature = 'fiches:export-scores {path=storage/fiche-scores.json}';

    protected $description = 'Export fiche scores to a JSON file for syncing to another environment';

    public function handle(): int
    {
        $fiches = Fiche::query()
            ->whereNotNull('quality_assessed_at')
            ->get(['id', 'slug', 'quality_score', 'quality_justification', 'quality_assessed_at', 'presentation_score', 'presentation_justification', 'completeness_score']);

        $path = $this->argument('path');
        file_put_contents($path, $fiches->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Exported {$fiches->count()} fiche scores to {$path}");

        return self::SUCCESS;
    }
}
