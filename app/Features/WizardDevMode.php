<?php

namespace App\Features;

use Laravel\Pennant\Attributes\Name;

#[Name('wizard-dev-mode')]
class WizardDevMode
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(mixed $scope): bool
    {
        return false;
    }
}
