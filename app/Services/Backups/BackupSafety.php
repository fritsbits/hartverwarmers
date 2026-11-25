<?php

namespace App\Services\Backups;

use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use ZipArchive;

class BackupSafety
{
    public function destination(): AwsS3V3Adapter
    {
        foreach (['key', 'secret', 'bucket', 'endpoint', 'region'] as $field) {
            if (! config('filesystems.disks.backups.'.$field)) {
                throw new RuntimeException('De back-updisk is onvolledig geconfigureerd.');
            }
        }

        if (! str_starts_with(config('filesystems.disks.backups.endpoint'), 'https://')) {
            throw new RuntimeException('De back-upbestemming moet HTTPS gebruiken.');
        }

        foreach (config('backups.objects.sources') as $source) {
            if (config('filesystems.disks.backups.bucket') === config('filesystems.disks.'.$source.'.bucket')) {
                throw new RuntimeException('De back-upbucket mag geen bronbucket zijn.');
            }
        }

        $disk = Storage::disk('backups');

        if (! $disk instanceof AwsS3V3Adapter) {
            throw new RuntimeException('De back-upbestemming moet een S3-disk zijn.');
        }

        return $disk;
    }

    public function archive(): void
    {
        $this->destination();

        if (strlen((string) config('backup.backup.password')) < 32 || config('backup.backup.encryption') !== 'aes256') {
            throw new RuntimeException('Back-ups vereisen AES-256 en een apart archiefwachtwoord van minstens 32 tekens.');
        }

        if (! class_exists(ZipArchive::class) || ! ZipArchive::isEncryptionMethodSupported(ZipArchive::EM_AES_256, true)) {
            throw new RuntimeException('PHP ZIP met AES-256-versleuteling ontbreekt.');
        }

        if (! filter_var(config('backup.notifications.mail.to'), FILTER_VALIDATE_EMAIL) || str_ends_with(config('backup.notifications.mail.to'), '.invalid')) {
            throw new RuntimeException('Stel een geldig BACKUP_NOTIFICATION_EMAIL in.');
        }

        if (! is_readable(base_path('.env'))) {
            throw new RuntimeException('Het gedeelde .env-bestand is niet leesbaar voor de back-up.');
        }

        $binaryDirectory = config('database.connections.mysql.dump.dump_binary_path');
        $binary = $binaryDirectory ? rtrim($binaryDirectory, '/').'/mysqldump' : (new ExecutableFinder)->find('mysqldump');

        if (! $binary || ! is_executable($binary)) {
            throw new RuntimeException('mysqldump ontbreekt; controleer BACKUP_MYSQLDUMP_PATH.');
        }

        $this->temporaryDirectory();
    }

    public function temporaryDirectory(int $additionalBytes = 0): string
    {
        umask(0077);
        $directory = config('backup.backup.temporary_directory');

        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('De private tijdelijke back-upmap kan niet worden aangemaakt.');
        }

        if (! chmod($directory, 0700)) {
            throw new RuntimeException('De tijdelijke back-upmap kan niet worden afgeschermd.');
        }

        $free = disk_free_space($directory);
        $minimum = max(1, (int) config('backups.minimum_free_bytes'));

        if ($free === false || $free < $minimum + $additionalBytes) {
            throw new RuntimeException('Onvoldoende vrije tijdelijke schijfruimte voor de back-up.');
        }

        return $directory;
    }

    public function verifyEncryptedArchive(string $path): void
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Het back-uparchief kan niet worden geopend.');
        }

        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->statIndex($index);

                if ($entry === false || (! str_ends_with($entry['name'], '/') && ($entry['encryption_method'] ?? null) !== ZipArchive::EM_AES_256)) {
                    throw new RuntimeException('Upload gestopt: niet alle bestanden zijn met AES-256 versleuteld.');
                }
            }
        } finally {
            $zip->close();
        }
    }
}
