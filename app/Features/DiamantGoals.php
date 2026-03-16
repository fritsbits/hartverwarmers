<?php

namespace App\Features;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Pennant\Attributes\Name;

#[Name('diamant-goals')]
class DiamantGoals
{
    public const CACHE_KEY = 'diamant-goals:live';

    private const ALLOWED_USER_IDS = [2623]; // Maite Mallentjer

    /**
     * Resolve the feature's initial value.
     *
     * Checks a cache flag for global activation first.
     * Falls back to beta tester list (admins + allowed IDs).
     */
    public function resolve(?User $scope): bool
    {
        if (Cache::get(self::CACHE_KEY)) {
            return true;
        }

        if (! $scope) {
            return false;
        }

        return $scope->isAdmin() || in_array($scope->id, self::ALLOWED_USER_IDS);
    }
}
