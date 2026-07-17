<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProductUpdates
{
    const FRESH_DAYS = 60;

    /**
     * All product updates from the content disk, newest first.
     *
     * @return Collection<int, array{uid: string, published_at: string, title: string, body: string, link?: array{url: string, label: string}}>
     */
    public static function all(): Collection
    {
        return collect(JsonContent::disk()->files('updates'))
            ->map(fn (string $file) => JsonContent::getContent('updates/'.basename($file, '.json')))
            ->filter(fn (array|false $update) => is_array($update)
                && isset($update['uid'], $update['published_at'], $update['title'], $update['body']))
            ->sortByDesc(fn (array $update) => $update['published_at'].'|'.$update['uid'])
            ->values();
    }

    /**
     * The newest update, or null when there is none or it is older than 60 days.
     *
     * @return array{uid: string, published_at: string, title: string, body: string, link?: array{url: string, label: string}}|null
     */
    public static function latestFresh(Carbon $now): ?array
    {
        $update = self::all()->first();

        if ($update === null) {
            return null;
        }

        if (Carbon::parse($update['published_at'])->lt($now->copy()->subDays(self::FRESH_DAYS))) {
            return null;
        }

        return $update;
    }
}
