<?php

namespace App\Features;

use Laravel\Pennant\Attributes\Name;

#[Name('diamant-goals')]
class DiamantGoals
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(mixed $scope): bool
    {
        return false;
    }
}
