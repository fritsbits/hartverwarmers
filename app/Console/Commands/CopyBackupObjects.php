<?php

namespace App\Console\Commands;

use App\Services\Backups\BackupAlerts;
use App\Services\Backups\ObjectBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class CopyBackupObjects extends Command
{
    protected $signature = 'backups:copy-objects {--dry-run : Alleen inventariseren, geen S3-objecten schrijven}';

    protected $description = 'Kopieer beide S3-bronbuckets incrementeel en bewaar eerdere objectversies';

    public function handle(ObjectBackup $backup, BackupAlerts $alerts): int
    {
        $lock = Cache::lock('backups:copy-objects', 86400);

        if (! $lock->get()) {
            $this->components->error('Een S3-back-up is al actief.');

            return self::FAILURE;
        }

        try {
            $result = $backup->copy((bool) $this->option('dry-run'));
            $this->components->info(sprintf('%s: %d nieuwe/gewijzigde objecten, %d bestaande kopieën.',
                $this->option('dry-run') ? 'Inventaris' : 'Voltooid', $result['copied'], $result['reused']));

            if ($result['manifest']) {
                $this->line('Herstelmanifest: '.$result['manifest']);
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $alerts->recordFailure($exception);
            $message = 'De S3-back-up is mislukt; er is geen nieuw volledig herstelmanifest gepubliceerd.';
            $this->components->error($message.' Fouttype: '.$exception::class);

            if (! $this->option('dry-run')) {
                $alerts->send($message);
            }

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }
}
