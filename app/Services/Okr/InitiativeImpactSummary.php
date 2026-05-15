<?php

namespace App\Services\Okr;

use App\Models\Okr\Initiative;
use Illuminate\Support\Collection;

final class InitiativeImpactSummary
{
    /**
     * @param  Collection<int, InitiativeKrImpact>  $krImpacts
     */
    public function __construct(
        public readonly Initiative $initiative,
        public readonly Collection $krImpacts,
    ) {}

    public function krImpactFor(int $keyResultId): ?InitiativeKrImpact
    {
        return $this->krImpacts->firstWhere('krId', $keyResultId);
    }
}
