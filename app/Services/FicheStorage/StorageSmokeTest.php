<?php

namespace App\Services\FicheStorage;

use App\Models\File;
use App\Services\PdfConverter;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Proves the server and both buckets work as designed before any real file
 * goes there. The preflight is quick and runs inside files:storage-prepare;
 * the live test uploads and downloads test objects under _migration-check/
 * from a queued job, and removes every version of them afterwards.
 *
 * It never creates buckets or credentials and never changes bucket policy.
 */
class StorageSmokeTest
{
    private const PREFIX = '_migration-check/';

    private const BUCKET_ATTEMPTS = 3;

    public function __construct(
        private FicheStorage $storage,
        private MigrationRegister $register,
        private ObjectVersions $versions,
        private BucketHealth $buckets,
    ) {}

    /**
     * @return array{ok: bool, checks: array<int, array{name: string, ok: bool, detail: string}>}
     */
    public function preflight(): array
    {
        $checks = [];
        $add = function (string $name, bool $ok, string $detail = '') use (&$checks) {
            $checks[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
        };

        $add('PHP-versie', true, PHP_VERSION);
        foreach (['pdo_sqlite', 'zip', 'imagick'] as $extension) {
            $add("Extensie {$extension}", extension_loaded($extension));
        }

        $soffice = PdfConverter::sofficeBinary();
        $add('LibreOffice (soffice)', is_executable($soffice) || $this->onPath($soffice), $soffice);
        $add('pdftotext', $this->onPath('pdftotext'));

        $root = $this->storage->workspaceRoot();
        $free = (int) @disk_free_space($root);
        $largest = (int) File::query()->max('size_bytes');
        $add('Werkmap schrijfbaar', is_writable($root), $root);
        $add('Vrije ruimte', $free >= max((int) config('fiche-storage.downloads.min_free_bytes'), $largest * 3), number_format($free / 1024 ** 3, 1).' GB vrij, grootste bestand '.number_format($largest / 1024 ** 2, 1).' MB');
        $add('Lokale opslag schrijfbaar', is_writable(storage_path('app/public')), storage_path('app/public'));

        try {
            $this->register->putMeta('preflight_probe', now()->timestamp);
            $add('Migratieregister', true, (string) config('database.connections.'.config('fiche-storage.migration.connection').'.database'));
        } catch (Throwable $e) {
            $add('Migratieregister', false, $e->getMessage());
        }

        $queue = config('queue.default');
        $retryAfter = (int) config("queue.connections.{$queue}.retry_after");
        $add('Queue retry_after ≥ 420', $queue === 'sync' || $retryAfter >= 420, "{$queue}: {$retryAfter}");

        $add('Scheduler actief', $this->register->meta('mode') !== null, 'files:storage-supervise moet minstens één keer gelopen hebben.');

        if (! $this->storage->remoteConfigured()) {
            $add('FICHE_STORAGE_* ingevuld', false, 'Vul key, secret, endpoint, region en beide bucketnamen in via Forge Environment.');

            return $this->finish($checks);
        }

        $add('FICHE_STORAGE_* ingevuld', true);

        foreach ([FicheStorage::FILES, FicheStorage::PREVIEWS] as $role) {
            $disk = $this->storage->remoteDisk($role);

            if (! $disk instanceof AwsS3V3Adapter) {
                $add("Bucket {$role}", false, 'Geen S3-schijf.');

                continue;
            }

            [$ok, $detail] = $this->headBucket($disk);
            $add("Bucket {$role} bereikbaar", $ok, $detail);

            $config = $disk->getConfig();
            $add("Bucket {$role} virtual-hosted", ($config['use_path_style_endpoint'] ?? false) === false);
        }

        $files = $this->storage->remoteDisk(FicheStorage::FILES);
        if ($files instanceof AwsS3V3Adapter) {
            try {
                $status = $files->getClient()->getBucketVersioning(['Bucket' => $files->getConfig()['bucket']])['Status'] ?? 'Off';
                $add('Versioning op bestandenbucket', $status === 'Enabled', "Status={$status}");
            } catch (Throwable $e) {
                $add('Versioning op bestandenbucket', false, $e->getMessage());
            }
        }

        if (collect($checks)->every(fn (array $check) => $check['ok'])) {
            try {
                $this->buckets->writeSentinels();
                $add('Sentinels voor de gezondheidscheck', true, (string) config('fiche-storage.health.sentinel'));
            } catch (Throwable $e) {
                $add('Sentinels voor de gezondheidscheck', false, $e->getMessage());
            }
        }

        return $this->finish($checks);
    }

    /**
     * The live test. Writes its outcome to the register for the health page.
     *
     * @return array{status: string, checks: array<int, array{name: string, ok: bool, detail: string}>}
     */
    public function run(): array
    {
        $checks = [];
        $add = function (string $name, bool $ok, string $detail = '') use (&$checks) {
            $checks[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
        };

        $prefix = self::PREFIX.now()->format('YmdHis').'/';
        $files = $this->storage->remoteDisk(FicheStorage::FILES);
        $previews = $this->storage->remoteDisk(FicheStorage::PREVIEWS);
        $written = [FicheStorage::FILES => [], FicheStorage::PREVIEWS => []];

        $this->register->putMeta('smoketest', ['status' => 'running', 'started_at' => now()->timestamp, 'checks' => []]);

        try {
            $this->storage->withWorkspace(function (string $workspace) use ($add, $prefix, $files, $previews, &$written) {
                $size = max((int) File::query()->max('size_bytes'), 1024 * 1024);
                $large = $workspace.'/large.bin';
                $handle = fopen($large, 'wb');
                $block = random_bytes(1024 * 1024);
                for ($remaining = $size; $remaining > 0; $remaining -= strlen($block)) {
                    fwrite($handle, substr($block, 0, min($remaining, strlen($block))));
                }
                fclose($handle);

                $started = microtime(true);
                $stream = fopen($large, 'rb');
                $files->writeStream($prefix.'large.bin', $stream);
                fclose($stream);
                $written[FicheStorage::FILES][] = $prefix.'large.bin';
                $uploadSeconds = round(microtime(true) - $started, 1);

                $started = microtime(true);
                $matches = $this->storage->hashOf($files, $prefix.'large.bin') === hash_file('sha256', $large);
                $add('Grote upload als stream en teruggelezen', $matches, number_format($size / 1024 ** 2, 1)." MB, upload {$uploadSeconds} s, download ".round(microtime(true) - $started, 1).' s');

                $files->put($prefix.'private.txt', 'privé');
                $written[FicheStorage::FILES][] = $prefix.'private.txt';
                $status = Http::timeout(15)->get($files->url($prefix.'private.txt'))->status();
                $add('Origineel niet anoniem leesbaar', in_array($status, [401, 403], true), "HTTP {$status}");

                $previews->put($prefix.'preview.jpg', 'preview-bytes');
                $written[FicheStorage::PREVIEWS][] = $prefix.'preview.jpg';
                $url = $previews->url($prefix.'preview.jpg');
                $response = Http::timeout(15)->get($url);
                $add('Preview anoniem leesbaar', $response->status() === 200 && $response->body() === 'preview-bytes', "HTTP {$response->status()} op {$url}");
                $expectedHost = 'https://'.$previews->getConfig()['bucket'].'.'.parse_url($previews->getConfig()['endpoint'], PHP_URL_HOST).'/';
                $add('Publieke URL-vorm', str_starts_with($url, $expectedHost), $url);

                $name = 'Smoketest – café “aanhalingstekens” ü.pdf';
                $response = Http::timeout(15)->get($this->storage->temporaryDownloadUrl($prefix.'private.txt', $name, 'application/pdf'));
                $disposition = (string) $response->header('Content-Disposition');
                $add('Presigned download met Unicode-naam', $response->status() === 200 && str_contains($disposition, "filename*=utf-8''") && str_starts_with((string) $response->header('Content-Type'), 'application/pdf'), "HTTP {$response->status()}, {$disposition}");

                $response = Http::timeout(30)->withHeaders(['Range' => 'bytes=0-1023'])
                    ->get($this->storage->temporaryDownloadUrl($prefix.'large.bin', 'large.bin', 'application/octet-stream'));
                $add('Range-request', $response->status() === 206 && strlen($response->body()) === 1024, "HTTP {$response->status()}, ".strlen($response->body()).' bytes');

                $expiring = $files->temporaryUrl($prefix.'private.txt', now()->addSeconds(2));
                sleep(4);
                $status = Http::timeout(15)->get($expiring)->status();
                $add('Verlopen link geweigerd', $status === 403, "HTTP {$status}");

                $files->put($prefix.'versioned.txt', 'versie 1');
                $files->put($prefix.'versioned.txt', 'versie 2');
                $written[FicheStorage::FILES][] = $prefix.'versioned.txt';
                $versions = $this->versions->versionCount($files, $prefix.'versioned.txt');
                $add('Overschrijven bewaart de vorige versie', $versions >= 2 && $files->get($prefix.'versioned.txt') === 'versie 2', "{$versions} versies");
            });
        } catch (Throwable $e) {
            $add('Onverwachte fout', false, class_basename($e).': '.$e->getMessage());
        } finally {
            foreach ($written as $role => $paths) {
                foreach ($paths as $path) {
                    try {
                        $this->versions->deleteAllVersions($this->storage->remoteDisk($role), $path);
                    } catch (Throwable $e) {
                        $add("Opruimen {$path}", false, $e->getMessage());
                    }
                }
            }
        }

        $result = [
            'status' => collect($checks)->every(fn (array $check) => $check['ok']) ? 'passed' : 'failed',
            'finished_at' => now()->timestamp,
            'checks' => $checks,
        ];

        $this->register->putMeta('smoketest', $result);

        return $result;
    }

    /**
     * New buckets can take up to fifteen minutes to become active: retry a
     * few times, then report the error instead of treating it as "missing".
     *
     * @return array{0: bool, 1: string}
     */
    private function headBucket(AwsS3V3Adapter $disk): array
    {
        $error = '';

        for ($attempt = 1; $attempt <= self::BUCKET_ATTEMPTS; $attempt++) {
            try {
                $disk->getClient()->headBucket(['Bucket' => $disk->getConfig()['bucket'], '@http' => ['timeout' => 10, 'connect_timeout' => 5]]);

                return [true, $disk->getConfig()['bucket']];
            } catch (Throwable $e) {
                $error = class_basename($e).': '.str($e->getMessage())->limit(200);

                if ($attempt < self::BUCKET_ATTEMPTS) {
                    sleep(10);
                }
            }
        }

        return [false, $error.' (na '.self::BUCKET_ATTEMPTS.' pogingen; een nieuwe bucket kan tot 15 minuten nodig hebben)'];
    }

    private function onPath(string $binary): bool
    {
        return Process::timeout(5)->run(['sh', '-c', 'command -v '.escapeshellarg($binary)])->successful();
    }

    /**
     * @param  array<int, array{name: string, ok: bool, detail: string}>  $checks
     * @return array{ok: bool, checks: array<int, array{name: string, ok: bool, detail: string}>}
     */
    private function finish(array $checks): array
    {
        $result = ['ok' => collect($checks)->every(fn (array $check) => $check['ok']), 'checks' => $checks];
        $this->register->putMeta('preflight', $result + ['at' => now()->timestamp]);

        return $result;
    }
}
