<?php

namespace App\Console\Commands;

use App\Models\Fiche;
use App\Services\FichePurger;
use Illuminate\Console\Command;

/**
 * Soft-deleted fiches keep their uploaded files on the public disk, so they
 * stay downloadable under /storage long after they disappeared from the site.
 * This command clears that backlog for good.
 */
class PurgeTrashedFichesCommand extends Command
{
    protected $signature = 'fiche:purge-trashed {--force : Skip the confirmation prompt}';

    protected $description = 'Permanently delete soft-deleted fiches, including their files on disk';

    public function handle(FichePurger $purger): int
    {
        $fiches = Fiche::onlyTrashed()->withCount('files')->get();

        if ($fiches->isEmpty()) {
            $this->info('Geen verwijderde fiches gevonden.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Titel', 'Verwijderd op', 'Bestanden'],
            $fiches->map(fn (Fiche $fiche) => [
                $fiche->id,
                $fiche->title,
                $fiche->deleted_at->format('d-m-Y'),
                $fiche->files_count,
            ])->all(),
        );

        if (! $this->option('force') && ! $this->confirm("{$fiches->count()} fiche(s) definitief wissen, inclusief hun bestanden?")) {
            $this->comment('Afgebroken.');

            return self::SUCCESS;
        }

        $files = 0;
        $images = 0;

        foreach ($fiches as $fiche) {
            $summary = $purger->purge($fiche);
            $files += $summary['files'];
            $images += $summary['images'];
        }

        $this->info("{$fiches->count()} fiche(s) gewist, samen met {$files} bestand(en) en {$images} afbeelding(en).");

        return self::SUCCESS;
    }
}
