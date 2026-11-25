<?php

namespace App\Console\Commands;

use App\Services\FicheStorage\StorageStatus;
use Illuminate\Console\Command;

class FicheStorageStatusCommand extends Command
{
    protected $signature = 'files:storage-status';

    protected $description = 'Toon de stand van de fiche-opslag (zelfde rapport als /admin/gezondheid)';

    public function handle(StorageStatus $status): int
    {
        $this->line(json_encode($status->report(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
