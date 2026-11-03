<?php

/*
|--------------------------------------------------------------------------
| Fiche storage
|--------------------------------------------------------------------------
|
| Uploaded fiche files (originals and converted pdf's) and their previews
| move from the local public disk to Hetzner Object Storage. The mode
| decides which physical disks are read and written; the migration
| commands always copy between the fixed local and remote disks below.
|
| local        read local,              write local
| mirror       read local, then remote, write local and remote
| s3-fallback  read remote, then local, write remote
| s3           read remote,             write remote
|
*/

return [

    'mode' => env('FICHE_STORAGE_MODE', 'local'),

    'disks' => [
        'files' => [
            'local' => 'fiche-files-local',
            'remote' => 'fiche-files-s3',
        ],
        'previews' => [
            'local' => 'fiche-previews-local',
            'remote' => 'fiche-previews-s3',
        ],
    ],

    /*
     * Temporary working directories for conversions, previews and zips.
     * Abandoned directories older than the maximum age are removed by
     * files:storage-supervise, so a killed worker never leaks disk space.
     */
    'workspace' => [
        'root' => env('FICHE_STORAGE_WORKSPACE', storage_path('app/fiche-workspaces')),
        'max_age_minutes' => 120,
    ],

    'downloads' => [
        'presigned_minutes' => 5,
        'max_concurrent_zips' => (int) env('FICHE_STORAGE_MAX_CONCURRENT_ZIPS', 3),
        'min_free_bytes' => (int) env('FICHE_STORAGE_ZIP_MIN_FREE_BYTES', 2 * 1024 ** 3),
    ],

    'locks' => [
        'object_seconds' => 300,
        'wait_seconds' => 60,
    ],

    'migration' => [
        /*
         * Durable register with manifests, checksums, deletions and progress:
         * a SQLite file in Forge's shared storage path, so it survives
         * releases and worker restarts without touching the app database.
         */
        'directory' => storage_path('app/private/fiche-storage-migration'),
        'connection' => 'fiche-storage-register',
        'inventory_chunk' => 200,
        'copy_batch' => 20,
        'pump_delay_seconds' => 20,
        'max_rounds' => 6,
        'max_attempts' => 5,

        /*
         * Old processes may keep writing only locally for a while after a
         * mode change: queued jobs, long CLI commands and in-flight web
         * requests. A mode only counts as drained after this grace period
         * and once no artisan process predates the mode change.
         */
        'drain_grace_minutes' => (int) env('FICHE_STORAGE_DRAIN_GRACE_MINUTES', 15),

        'retention_days' => 14,
        'cleanup_plan_valid_hours' => 24,
    ],

    'health' => [
        'sentinel' => '_health/sentinel.txt',
        'timeout_seconds' => 5,
    ],

];
