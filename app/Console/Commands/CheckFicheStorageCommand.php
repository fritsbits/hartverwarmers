<?php

namespace App\Console\Commands;

use App\Enums\FicheStorageMode;
use App\Services\FicheStorage\TransitionCheck;
use Illuminate\Console\Command;

/**
 * Deploy gate. Runs in the Forge deploy script after config:cache and
 * before $ACTIVATE_RELEASE(); a failure aborts the release.
 */
class CheckFicheStorageCommand extends Command
{
    protected $signature = 'files:storage-check {--target= : local, mirror, s3-fallback of s3 (standaard: de ingestelde FICHE_STORAGE_MODE)}';

    protected $description = 'Controleer of deze release met de gekozen opslagmodus live mag gaan';

    public function handle(TransitionCheck $check): int
    {
        $target = FicheStorageMode::tryFrom((string) ($this->option('target') ?? config('fiche-storage.mode')));

        if ($target === null) {
            $this->components->error('Onbekende modus. Kies local, mirror, s3-fallback of s3.');

            return self::FAILURE;
        }

        $result = $check->check($target);

        if ($result['ok']) {
            $this->components->info("Opslagmodus {$result['target']} mag live (nu waargenomen: {$result['current']}).");

            return self::SUCCESS;
        }

        $this->components->error("Opslagmodus {$result['target']} mag nog niet live (nu waargenomen: {$result['current']}).");
        $this->components->bulletList($result['reasons']);

        return self::FAILURE;
    }
}
