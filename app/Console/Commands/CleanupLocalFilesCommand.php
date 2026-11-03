<?php

namespace App\Console\Commands;

use App\Services\FicheStorage\LocalCleanup;
use Illuminate\Console\Command;

/**
 * Two explicit steps: --dry-run writes and shows a plan; --execute runs only
 * that plan. Never part of a deploy.
 */
class CleanupLocalFilesCommand extends Command
{
    protected $signature = 'files:cleanup-local
        {--dry-run : Maak en toon een opruimplan zonder iets te verwijderen}
        {--execute : Voer een eerder getoond plan uit}
        {--run= : Id van het plan dat --dry-run toonde}';

    protected $description = 'Ruim geverifieerde lokale fiche-bestanden op nadat de bucket de enige bron is';

    public function handle(LocalCleanup $cleanup): int
    {
        if ($this->option('dry-run') === $this->option('execute')) {
            $this->components->error('Kies --dry-run of --execute --run=<id>.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $plan = $cleanup->plan();

            if (! $plan['ok']) {
                $this->components->error('Opruimen kan nog niet:');
                $this->components->bulletList($plan['reasons']);

                return self::FAILURE;
            }

            $this->components->info("Plan {$plan['run_id']}: {$plan['items']} bestand(en), ".number_format($plan['bytes'] / 1024 ** 3, 2).' GB.');
            $this->line("{$plan['unknown']} onbekend(e) bestand(en) blijven staan.");
            foreach ($plan['unknown_sample'] as $path) {
                $this->line("  {$path}");
            }
            $this->line("Voer uit met: files:cleanup-local --execute --run={$plan['run_id']}");

            return self::SUCCESS;
        }

        if (! $this->option('run')) {
            $this->components->error('Geef het plan mee met --run=<id>.');

            return self::FAILURE;
        }

        $result = $cleanup->execute($this->option('run'));

        if (! $result['ok']) {
            $this->components->error('Plan niet uitgevoerd:');
            $this->components->bulletList($result['reasons']);

            return self::FAILURE;
        }

        $this->components->info('Opruiming ingepland. Volg de voortgang op /admin/gezondheid.');

        return self::SUCCESS;
    }
}
