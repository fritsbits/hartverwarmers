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
        public ?array $productUpdate = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->upcomingThemeCount === 0 && $this->newFicheCount === 0;
    }

    /**
     * Personalise the shared payload for one recipient. A user never sees
     * the same diamond in two consecutive digests, and never receives the
     * same product update twice.
     */
    public function forUser(User $user): self
    {
        $dropDiamond = $this->diamond && $this->diamond->id === $user->last_digest_diamond_fiche_id;
        $dropUpdate = $this->productUpdate && $user->hasSeenProductUpdate($this->productUpdate['uid']);

        if (! $dropDiamond && ! $dropUpdate) {
            return $this;
        }

        $personalised = clone $this;

        if ($dropDiamond) {
            $personalised->diamond = null;
        }

        if ($dropUpdate) {
            $personalised->productUpdate = null;
        }

        return $personalised;
    }
}
