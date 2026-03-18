<?php

namespace App\Console\Commands;

use App\Models\Fiche;
use Illuminate\Console\Command;

class ImportFicheSuggestions extends Command
{
    protected $signature = 'fiches:import-suggestions {path=storage/fiche-suggestions.json}';

    protected $description = 'Import fiche AI suggestions from a JSON file exported by fiches:export-suggestions';

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
                'ai_suggestions' => $row['ai_suggestions'],
            ]);

            $updated++;
        }

        $this->info("Imported {$updated} fiche suggestions. Skipped {$skipped}.");

        return self::SUCCESS;
    }
}
