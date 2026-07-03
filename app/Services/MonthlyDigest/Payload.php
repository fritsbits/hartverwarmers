<?php

namespace App\Services\MonthlyDigest;

use App\Models\Fiche;
use App\Models\ThemeOccurrence;
use App\Models\User;
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

    /**
     * A user must never see the same diamond in two consecutive digests.
     * When the current diamond is the one from their previous digest, the
     * section is dropped for them rather than repeated.
     */
    public function forUser(User $user): self
    {
        if ($this->diamond && $this->diamond->id === $user->last_digest_diamond_fiche_id) {
            $personalised = clone $this;
            $personalised->diamond = null;

            return $personalised;
        }

        return $this;
    }
}
