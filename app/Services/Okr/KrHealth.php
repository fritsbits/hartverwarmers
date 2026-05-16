<?php

namespace App\Services\Okr;

/**
 * Maps a Key Result's current value to a health status the admin can read at
 * a glance. Two regimes:
 *
 *  - Absolute-scale metrics (a 0–100 quality score) are judged on their own
 *    value, matching the documented score-color convention
 *    (green ≥70 / amber 40–69 / red <40), independent of any target.
 *  - Every other metric is judged goal-relative: green only once the target
 *    is reached, amber while on the way (≥40% of target), red when far off.
 *    "Below goal" is never green so the colour answers "are we there?".
 *    Without a target there is no notion of "good", so the status is neutral
 *    and the value keeps the brand-primary colour (no false judgement).
 */
final class KrHealth
{
    /** Metrics measured on an absolute 0–100 scale where the value is self-describing. */
    private const ABSOLUTE_SCORE_METRICS = ['presentation_score_avg'];

    public static function status(int|float|null $current, ?int $target, ?string $metricKey = null): string
    {
        if ($current === null) {
            return 'neutral';
        }

        if (in_array($metricKey, self::ABSOLUTE_SCORE_METRICS, true)) {
            return self::band((float) $current);
        }

        if ($target === null || $target <= 0) {
            return 'neutral';
        }

        // Goal-relative: green only once the target is reached, amber while on
        // the way, red when far off. "Below goal" is never green — the colour
        // answers "are we there?" at a glance.
        return match (true) {
            $current >= $target => 'good',
            $current / $target * 100 >= 40 => 'mid',
            default => 'bad',
        };
    }

    public static function colorClass(int|float|null $current, ?int $target, ?string $metricKey = null): string
    {
        return match (self::status($current, $target, $metricKey)) {
            'good' => 'text-green-700',
            'mid' => 'text-amber-600',
            'bad' => 'text-red-600',
            default => 'text-[var(--color-primary)]',
        };
    }

    public static function barClass(int|float|null $current, ?int $target, ?string $metricKey = null): string
    {
        return match (self::status($current, $target, $metricKey)) {
            'good' => 'bg-green-600',
            'mid' => 'bg-amber-500',
            'bad' => 'bg-red-500',
            default => 'bg-[var(--color-primary)]',
        };
    }

    private static function band(float $value): string
    {
        return match (true) {
            $value >= 70 => 'good',
            $value >= 40 => 'mid',
            default => 'bad',
        };
    }
}
