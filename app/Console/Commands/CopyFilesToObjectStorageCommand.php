<?php

namespace App\Console\Commands;

use App\Services\FicheStorage\FicheStorage;
use App\Services\FicheStorage\StorageMigration;
use Illuminate\Console\Command;
use Throwable;

class CopyFilesToObjectStorageCommand extends Command
{
    protected $signature = 'files:copy-to-object-storage {--queued : Plan het werk op de queue en keer meteen terug}';

    protected $description = 'Start of hervat het kopiëren en verifiëren van alle fiche-bestanden naar de bucket';

    public function handle(FicheStorage $storage, StorageMigration $migration): int
    {
        if (! $this->option('queued')) {
            $this->components->error('Gebruik --queued: de overdracht loopt altijd als achtergrondwerk.');

            return self::FAILURE;
        }

        if (! $storage->mode()->usesRemote() || ! $storage->remoteConfigured()) {
            $this->components->error('Kopiëren kan pas vanaf modus mirror, met ingevulde FICHE_STORAGE_*-gegevens.');

            return self::FAILURE;
        }

        try {
            $result = $migration->start(StorageMigration::TO_REMOTE);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info(($result['resumed'] ? 'Bestaande run hervat: ' : 'Run ingepland: ').$result['run_id']);
        $this->line('Volg de voortgang op /admin/gezondheid. Alleen de status "verified" betekent dat alles gekopieerd en gecontroleerd is.');

        return self::SUCCESS;
    }
}
