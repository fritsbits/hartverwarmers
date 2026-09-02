<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Wanneer iets gepost is, in mensentaal.
 *
 * Vers werk telt af in dagen ("vandaag", "gisteren", "4 dagen geleden") en mag
 * opvallen. Alles ouder dan twee weken zakt terug naar een maandnaam
 * ("augustus", "november 2025") — een aftelling van maanden zegt daar niets
 * meer en zou de lijst enkel volzetten.
 */
class PostedMoment
{
    /** Vanaf hoeveel dagen oud een fiche geen nieuws meer is. */
    public const FRESH_DAYS = 14;

    private const TIMEZONE = 'Europe/Brussels';

    private function __construct(
        public string $label,
        public bool $isFresh,
    ) {}

    public static function for(CarbonInterface $moment, ?CarbonInterface $now = null): self
    {
        $posted = CarbonImmutable::instance($moment)->setTimezone(self::TIMEZONE);
        $today = ($now ? CarbonImmutable::instance($now) : CarbonImmutable::now())
            ->setTimezone(self::TIMEZONE);

        $days = $posted->startOfDay()->diffInDays($today->startOfDay());

        if ($days < 0) {
            return new self('vandaag', true);
        }

        if ($days < self::FRESH_DAYS) {
            return new self(self::freshLabel($posted, $today, $days), true);
        }

        $month = $posted->locale('nl_BE')->translatedFormat('F');

        return new self(
            $posted->year === $today->year ? $month : "{$month} {$posted->year}",
            false,
        );
    }

    private static function freshLabel(CarbonImmutable $posted, CarbonImmutable $today, int $days): string
    {
        return match (true) {
            $days === 0 && $posted->lessThanOrEqualTo($today) && $posted->diffInMinutes($today) < 60 => 'net gepost',
            $days === 0 => 'vandaag',
            $days === 1 => 'gisteren',
            $days < 7 => "{$days} dagen geleden",
            default => 'vorige week',
        };
    }
}
