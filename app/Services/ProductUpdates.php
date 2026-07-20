<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductUpdates
{
    const FRESH_DAYS = 60;

    /**
     * All product updates from the content disk, newest first.
     *
     * @return Collection<int, array{uid: string, published_at: string, title: string, body: string, content?: string, image?: array{src: string, alt: string}, link?: array{url: string, label: string}}>
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
     * A single update by uid, or null when it does not exist.
     *
     * @return array{uid: string, published_at: string, title: string, body: string, content?: string, image?: array{src: string, alt: string}, link?: array{url: string, label: string}}|null
     */
    public static function find(string $uid): ?array
    {
        return self::all()->firstWhere('uid', $uid);
    }

    /**
     * The newest update, or null when there is none or it is older than 60 days.
     *
     * @return array{uid: string, published_at: string, title: string, body: string, content?: string, image?: array{src: string, alt: string}, link?: array{url: string, label: string}}|null
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

    /**
     * The update published just after $uid (newer), or null at the top of the list.
     *
     * @return array{uid: string, published_at: string, title: string, body: string, content?: string, image?: array{src: string, alt: string}, link?: array{url: string, label: string}}|null
     */
    public static function newerThan(string $uid): ?array
    {
        $updates = self::all();
        $index = $updates->search(fn (array $update) => $update['uid'] === $uid);

        return $index === false ? null : $updates->get($index - 1);
    }

    /**
     * The update published just before $uid (older), or null at the bottom of the list.
     *
     * @return array{uid: string, published_at: string, title: string, body: string, content?: string, image?: array{src: string, alt: string}, link?: array{url: string, label: string}}|null
     */
    public static function olderThan(string $uid): ?array
    {
        $updates = self::all();
        $index = $updates->search(fn (array $update) => $update['uid'] === $uid);

        return $index === false ? null : $updates->get($index + 1);
    }

    /**
     * The update's long-form markdown rendered to HTML, or null when it has none.
     *
     * @param  array{content?: string}  $update
     */
    public static function renderContent(array $update): ?string
    {
        $content = $update['content'] ?? null;

        if (! is_string($content) || trim($content) === '') {
            return null;
        }

        return Str::markdown($content, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
