<?php

namespace App\Features;

use App\Models\User;
use Laravel\Pennant\Attributes\Name;

#[Name('doelenpagina-preview')]
class DoelenpaginaPreview
{
    /**
     * Admins en curatoren zien de nieuwe doelenpagina zonder geheime link.
     */
    public function resolve(?User $scope): bool
    {
        if (! $scope) {
            return false;
        }

        return $scope->isAdmin() || $scope->isCurator();
    }
}
