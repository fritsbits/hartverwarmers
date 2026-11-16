<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * The cache keys that show fiches on the homepage and in the footer.
 *
 * They live for minutes to an hour, so a fiche that is unpublished or deleted
 * would keep showing up until they expire. The FicheObserver flushes them on
 * every save and delete; callers that write with updateQuietly() flush here
 * themselves.
 */
class HomeCache
{
    private const KEYS = [
        'home:recent-fiches',
        'home:recent-diamond',
        'home:stats',
        'footer_stats',
    ];

    public static function flush(): void
    {
        foreach (self::KEYS as $key) {
            Cache::forget($key);
        }
    }
}
