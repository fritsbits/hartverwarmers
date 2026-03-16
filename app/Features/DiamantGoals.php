<?php

namespace App\Features;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Attributes\Name;

#[Name('diamant-goals')]
class DiamantGoals
{
    private const ALLOWED_USER_IDS = [2623]; // Maite Mallentjer

    /**
     * Resolve the feature's initial value.
     *
     * Checks if globally activated (null-scope stored true) first.
     * Falls back to beta tester list (admins + allowed IDs).
     */
    public function resolve(?User $scope): bool
    {
        $globallyActive = DB::table('features')
            ->where('name', 'diamant-goals')
            ->where('scope', '__laravel_null')
            ->where('value', 'true')
            ->exists();

        if ($globallyActive) {
            return true;
        }

        if (! $scope) {
            return false;
        }

        return $scope->isAdmin() || in_array($scope->id, self::ALLOWED_USER_IDS);
    }
}
