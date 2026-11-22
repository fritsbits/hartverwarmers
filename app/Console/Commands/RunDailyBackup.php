<?php

namespace App\Console\Commands;

use App\Services\Backups\BackupAlerts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RunDailyBackup extends Command
{
    protected $signature = 'backups:daily';

    protected $description = 'Maak app-/database- en S3-back-ups en pas daarna het archiefbewaarbeleid toe';

    public function handle(BackupAlerts $alerts): int
    {
        if (! config('backups.enabled')) {
            $this->components->warn('Back-ups zijn uitgeschakeld; stel BACKUPS_ENABLED pas na de voorbereiding in.');

            return self::FAILURE;
        }

        $lock = Cache::lock('backups:daily', 86400);

        if (! $lock->get()) {
            $this->components->error('De dagelijkse back-up is al actief.');

            return self::FAILURE;
        }

        try {
            if ($this->call('backup:run', ['--isolated' => 1]) !== self::SUCCESS) {
                throw new \RuntimeException('App-/databasearchief mislukt.');
            }

            if ($this->call('backups:copy-objects') !== self::SUCCESS) {
                throw new \RuntimeException('S3-kopie mislukt.');
            }

            if ($this->call('backup:clean', ['--isolated' => 1]) !== self::SUCCESS) {
                throw new \RuntimeException('Archiefbewaarbeleid mislukt.');
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $alerts->recordFailure($exception);
            $this->components->error('De dagelijkse back-up is niet volledig geslaagd. Fouttype: '.$exception::class);
            $alerts->send('De dagelijkse back-up is niet volledig geslaagd. Controleer archief, objectmanifest en bewaarbeleid.');

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }
}
