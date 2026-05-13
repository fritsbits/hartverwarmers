<?php

namespace App\Services\MonthlyDigest;

use App\Models\Fiche;
use App\Models\ThemeOccurrence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Payload
{
    /**
     * @param  Collection<int, ThemeOccurrence>  $themes
     * @param  Collection<int, Fiche>  $recentFiches
     */
    public function __construct(
        public Collection $themes,
        public ?Fiche $diamond,
        public Collection $recentFiches,
        public int $upcomingThemeCount,
        public int $newFicheCount,
        public Carbon $sentAt,
    ) {}

    public function isEmpty(): bool
    {
        return $this->upcomingThemeCount === 0 && $this->newFicheCount === 0;
    }
}
