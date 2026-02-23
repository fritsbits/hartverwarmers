<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class JsonContent
{
    const CONTENT_SUFFIX = '.json';

    public static function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk(config('content.disk'));
    }

    public static function getContent(string $name): array|false
    {
        $contentFile = $name.self::CONTENT_SUFFIX;
        $disk = self::disk();

        if (! $disk->exists($contentFile)) {
            return false;
        }

        $content = json_decode($disk->get($contentFile), true);

        return empty($content) ? false : $content;
    }
}
