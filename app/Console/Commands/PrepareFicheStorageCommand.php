<?php

namespace App\Console\Commands;

use App\Jobs\RunStorageSmokeTest;
use App\Services\FicheStorage\MigrationRegister;
use App\Services\FicheStorage\StorageSmokeTest;
use Illuminate\Console\Command;

/**
 * Checks server and buckets, then queues the live bucket test. Creates no
 * buckets or credentials and changes no bucket policy or storage mode.
 */
class PrepareFicheStorageCommand extends Command
{
    protected $signature = 'files:storage-prepare';

    protected $description = 'Controleer server en buckets en start de buckettest op de queue';

    public function handle(StorageSmokeTest $smokeTest, MigrationRegister $register): int
    {
        $preflight = $smokeTest->preflight();

        $this->table(
            ['Controle', 'Resultaat', 'Details'],
            array_map(fn (array $check) => [$check['name'], $check['ok'] ? 'ok' : 'NIET OK', $check['detail']], $preflight['checks']),
        );

        if (! $preflight['ok']) {
            $register->putMeta('smoketest', ['status' => 'blocked', 'finished_at' => now()->timestamp, 'checks' => []]);
            $this->components->error('Niet klaar: los de controles hierboven op en voer dit commando opnieuw uit.');

            return self::FAILURE;
        }

        $register->putMeta('smoketest', ['status' => 'queued', 'queued_at' => now()->timestamp, 'checks' => []]);
        RunStorageSmokeTest::dispatch();

        $this->components->info('Controles geslaagd. De buckettest is ingepland; het resultaat verschijnt op /admin/gezondheid.');

        return self::SUCCESS;
    }
}
