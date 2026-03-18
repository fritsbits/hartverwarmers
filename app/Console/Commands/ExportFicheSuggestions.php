<?php

namespace App\Console\Commands;

use App\Models\Fiche;
use Illuminate\Console\Command;

class ExportFicheSuggestions extends Command
{
    protected $signature = 'fiches:export-suggestions {path=storage/fiche-suggestions.json}';

    protected $description = 'Export fiche AI suggestions to a JSON file for syncing to another environment';

    public function handle(): int
    {
        $fiches = Fiche::query()
            ->whereNotNull('ai_suggestions')
            ->get(['id', 'slug', 'ai_suggestions']);

        $path = $this->argument('path');
        file_put_contents($path, $fiches->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Exported {$fiches->count()} fiche suggestions to {$path}");

        return self::SUCCESS;
    }
}
