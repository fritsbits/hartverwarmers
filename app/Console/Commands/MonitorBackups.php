<?php

namespace App\Console\Commands;

use App\Services\Backups\BackupAlerts;
use App\Services\Backups\BackupSafety;
use App\Services\Backups\ObjectBackup;
use Illuminate\Console\Command;
use Throwable;

class MonitorBackups extends Command
{
    protected $signature = 'backups:monitor';

    protected $description = 'Controleer app-/databasearchieven en het laatste volledige S3-herstelmanifest';

    public function handle(BackupSafety $safety, ObjectBackup $objects, BackupAlerts $alerts): int
    {
        $failed = false;

        try {
            $safety->destination();
            $failed = $this->call('backup:monitor') !== self::SUCCESS;
        } catch (Throwable $exception) {
            $alerts->recordFailure($exception);
            $failed = true;
            $alerts->send('De gezondheid van de app-/databaseback-up kon niet worden gecontroleerd.');
        }

        try {
            $objects->assertFresh();
            $this->components->info('Het volledige S3-herstelmanifest is aanwezig en jonger dan 36 uur.');
        } catch (Throwable $exception) {
            $alerts->recordFailure($exception);
            $failed = true;
            $alerts->send('De volledige S3-kopie ontbreekt, is ouder dan 36 uur of kan niet worden gelezen.');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
