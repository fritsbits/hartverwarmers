<?php

namespace App\Services\Backups;

use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Spatie\Backup\Commands\BackupCommand;
use Throwable;

class SecureBackupCommand extends BackupCommand
{
    public function handle(): int
    {
        $lock = Cache::lock('backups:archive', 86400);

        if (! $lock->get()) {
            $this->components->error('Een app-/databaseback-up is al actief.');

            return self::FAILURE;
        }

        $originalUmask = umask();

        try {
            if ($this->option('config') !== null) {
                throw new RuntimeException('Een afwijkende back-upconfiguratie is niet toegestaan.');
            }

            app(BackupSafety::class)->archive();

            return parent::handle();
        } catch (Throwable $exception) {
            app(BackupAlerts::class)->recordFailure($exception);
            $this->components->error('De archiefback-up is gestopt. Fouttype: '.$exception::class);
            app(BackupAlerts::class)->send('Het app-/databasearchief is gestopt. Controleer de veilige configuratie, mysqldump en vrije schijfruimte.');

            return self::FAILURE;
        } finally {
            umask($originalUmask);
            $lock->release();
        }
    }
}
