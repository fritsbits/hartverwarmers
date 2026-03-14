<?php

namespace App\Services;

use App\Models\Fiche;
use App\Models\User;
use App\Models\UserInteraction;

class FicheInteractionService
{
    /**
     * Returns a map of fiche ID → array of interaction types for the given user.
     *
     * @param  array<int>|Collection  $ficheIds
     * @return array<int, array<string>>
     */
    public function forUser(?User $user, $ficheIds): array
    {
        if (! $user || empty($ficheIds)) {
            return [];
        }

        return UserInteraction::where('user_id', $user->id)
            ->where('interactable_type', Fiche::class)
            ->whereIn('interactable_id', $ficheIds)
            ->get()
            ->groupBy('interactable_id')
            ->map(fn ($interactions) => $interactions->pluck('type')->all())
            ->all();
    }
}
