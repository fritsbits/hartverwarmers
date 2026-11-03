<?php

namespace App\Console\Commands;

use App\Enums\FicheStorageMode;
use App\Services\FicheStorage\FicheStorage;
use App\Services\FicheStorage\StorageMigration;
use Illuminate\Console\Command;
use Throwable;

class CopyFilesToLocalCommand extends Command
{
    protected $signature = 'files:copy-to-local {--queued : Plan het werk op de queue en keer meteen terug}';

    protected $description = 'Herstel ontbrekende of afwijkende lokale kopieën uit de bucket (rollback); wijzigt de modus niet';

    public function handle(FicheStorage $storage, StorageMigration $migration): int
    {
        if (! $this->option('queued')) {
            $this->components->error('Gebruik --queued: het herstel loopt altijd als achtergrondwerk.');

            return self::FAILURE;
        }

        if ($storage->mode() !== FicheStorageMode::Mirror || ! $storage->remoteConfigured()) {
            $this->components->error('Herstel naar lokaal kan alleen in modus mirror, zodat nieuwe uploads op beide plaatsen landen.');

            return self::FAILURE;
        }

        try {
            $result = $migration->start(StorageMigration::TO_LOCAL);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info(($result['resumed'] ? 'Bestaande run hervat: ' : 'Run ingepland: ').$result['run_id']);
        $this->line('Volg de voortgang op /admin/gezondheid.');

        return self::SUCCESS;
    }
}
