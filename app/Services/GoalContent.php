<?php

namespace App\Services;

class GoalContent
{
    private const BLOCKS = ['schoolvoorbeelden', 'verhalen', 'klassiekers', 'referenties'];

    /**
     * De content van één doel, met altijd alle blokken aanwezig.
     *
     * @return array{schoolvoorbeelden: array, verhalen: array, klassiekers: array, referenties: array}
     */
    public static function for(string $facetSlug): array
    {
        $empty = array_fill_keys(self::BLOCKS, []);

        if (! preg_match('/^[a-z0-9-]+$/', $facetSlug)) {
            return $empty;
        }

        $content = JsonContent::getContent('doelen/'.$facetSlug);

        if ($content === false) {
            return $empty;
        }

        foreach (self::BLOCKS as $block) {
            $empty[$block] = is_array($content[$block] ?? null) ? $content[$block] : [];
        }

        return $empty;
    }
}
