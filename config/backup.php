<?php

use App\Notifications\BackupNotifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

return [
    'backup' => [
        'name' => 'hartverwarmers-app',
        'source' => [
            'files' => [
                // Explicit paths also cover Forge's shared storage and .env symlinks.
                'include' => [
                    app_path(), base_path('bootstrap'), config_path(), database_path(), lang_path(),
                    public_path(), resource_path(), base_path('routes'), storage_path(),
                    base_path('artisan'), base_path('composer.json'), base_path('composer.lock'),
                    base_path('package.json'), base_path('package-lock.json'),
                    base_path('vite.config.js'), base_path('tailwind.config.js'), base_path('.env'),
                ],
                'exclude' => [
                    base_path('.git'), base_path('vendor'), base_path('node_modules'),
                    base_path('auth.json'), base_path('bootstrap/cache'),
                    storage_path('framework'), storage_path('logs'),
                    storage_path('app/backup-temp'),
                    storage_path('debugbar'), storage_path('app/fiche-workspaces'),
                    storage_path('app/private/fiche-storage-migration'),
                ],
                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => base_path(),
            ],
            'databases' => ['mysql'],
        ],
        'database_dump_compressor' => null,
        'database_dump_file_timestamp_format' => 'Y-m-d-H-i-s',
        'database_dump_filename_base' => 'connection',
        'database_dump_file_extension' => 'sql',
        'destination' => [
            'compression_method' => ZipArchive::CM_DEFLATE,
            'compression_level' => 6,
            'filename_prefix' => '',
            'disks' => ['backups'],
            'continue_on_failure' => false,
        ],
        'temporary_directory' => storage_path('app/backup-temp'),
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => 'aes256',
        'verify_backup' => true,
        'tries' => 1,
        'retry_delay' => 0,
    ],
    'notifications' => [
        'notifications' => [
            BackupHasFailedNotification::class => ['mail'],
            UnhealthyBackupWasFoundNotification::class => ['mail'],
            CleanupHasFailedNotification::class => ['mail'],
            BackupWasSuccessfulNotification::class => [],
            HealthyBackupWasFoundNotification::class => [],
            CleanupWasSuccessfulNotification::class => [],
        ],
        'notifiable' => BackupNotifiable::class,
        'mail' => [
            'to' => env('BACKUP_NOTIFICATION_EMAIL') ?: 'backups-unconfigured@example.invalid',
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Hartverwarmers'),
            ],
        ],
        'slack' => ['webhook_url' => '', 'channel' => null, 'username' => null, 'icon' => null],
    ],
    'log_channel' => null,
    'monitor_backups' => [[
        'name' => 'hartverwarmers-app',
        'disks' => ['backups'],
        'health_checks' => [
            MaximumAgeInDays::class => 1.5,
            MaximumStorageInMegabytes::class => 51200,
        ],
    ]],
    'cleanup' => [
        'strategy' => DefaultStrategy::class,
        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 23,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 12,
            'keep_yearly_backups_for_years' => 0,
            // Alert on growth; never shorten retention to fit a byte quota.
            'delete_oldest_backups_when_using_more_megabytes_than' => null,
        ],
        'tries' => 1,
        'retry_delay' => 0,
    ],
];
