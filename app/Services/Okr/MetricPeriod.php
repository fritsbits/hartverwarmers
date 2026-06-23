<?php

namespace App\Services\Okr;

final class MetricPeriod
{
    public static function label(string $range): string
    {
        return match ($range) {
            'week' => 'laatste 7 dagen',
            'quarter' => 'laatste 12 weken',
            'alltime' => 'sinds de start',
            default => 'laatste 30 dagen',
        };
    }
}
