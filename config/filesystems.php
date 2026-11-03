<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'content' => [
            'driver' => 'local',
            'root' => resource_path('content'),
        ],

        /*
         * Fiche files and previews. The local roots are exactly the public
         * disk root because stored keys already start with files/ or
         * file-previews/. Pick a disk through App\Services\FicheStorage\FicheStorage,
         * never directly from business code.
         */

        'fiche-files-local' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
        ],

        'fiche-previews-local' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', ''), '/').'/storage',
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
        ],

        'fiche-files-s3' => [
            'driver' => 's3',
            'key' => env('FICHE_STORAGE_ACCESS_KEY_ID'),
            'secret' => env('FICHE_STORAGE_SECRET_ACCESS_KEY'),
            'region' => env('FICHE_STORAGE_REGION', 'fsn1'),
            'bucket' => env('FICHE_STORAGE_FILES_BUCKET'),
            'endpoint' => env('FICHE_STORAGE_ENDPOINT', 'https://fsn1.your-objectstorage.com'),
            'use_path_style_endpoint' => false,
            'visibility' => 'private',
            'request_checksum_calculation' => 'when_required',
            'response_checksum_validation' => 'when_required',
            'http' => ['connect_timeout' => 10, 'timeout' => 120],
            'retries' => ['mode' => 'standard', 'max_attempts' => 3],
            'throw' => true,
            'report' => false,
        ],

        'fiche-previews-s3' => [
            'driver' => 's3',
            'key' => env('FICHE_STORAGE_ACCESS_KEY_ID'),
            'secret' => env('FICHE_STORAGE_SECRET_ACCESS_KEY'),
            'region' => env('FICHE_STORAGE_REGION', 'fsn1'),
            'bucket' => env('FICHE_STORAGE_PREVIEWS_BUCKET'),
            'endpoint' => env('FICHE_STORAGE_ENDPOINT', 'https://fsn1.your-objectstorage.com'),
            'use_path_style_endpoint' => false,
            'visibility' => 'public',
            'request_checksum_calculation' => 'when_required',
            'response_checksum_validation' => 'when_required',
            'http' => ['connect_timeout' => 10, 'timeout' => 120],
            'retries' => ['mode' => 'standard', 'max_attempts' => 3],
            'throw' => true,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
