<?php

namespace App\Console\Commands;

use App\Models\Fiche;
use Illuminate\Console\Command;

class ImportFicheScores extends Command
{
    protected $signature = 'fiches:import-scores {path=storage/fiche-scores.json}';

    protected $description = 'Import fiche scores from a JSON file exported by fiches:export-scores';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true);

        if (! is_array($data)) {
            $this->error('Invalid JSON file.');

            return self::FAILURE;
        }

        $updated = 0;
        $skipped = 0;

        foreach ($data as $row) {
            $fiche = Fiche::where('slug', $row['slug'])->first();

            if (! $fiche) {
                $this->warn("Fiche not found: {$row['slug']}");
                $skipped++;

                continue;
            }

            $fiche->updateQuietly([
                'quality_score' => $row['quality_score'],
                'quality_justification' => $row['quality_justification'],
                'quality_assessed_at' => $row['quality_assessed_at'],
                'presentation_score' => $row['presentation_score'],
                'presentation_justification' => $row['presentation_justification'],
                'completeness_score' => $row['completeness_score'],
            ]);

            $updated++;
        }

        $this->info("Imported {$updated} fiche scores. Skipped {$skipped}.");

        return self::SUCCESS;
    }
}
