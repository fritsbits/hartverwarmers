<?php

return [
    'enabled' => (bool) env('BACKUPS_ENABLED', false),
    'minimum_free_bytes' => (int) env('BACKUP_MIN_FREE_BYTES', 2 * 1024 ** 3),
    'objects' => [
        'prefix' => 's3-objects',
        'sources' => ['fiche-files-s3', 'fiche-previews-s3'],
        'maximum_age_hours' => 36,
    ],
];
