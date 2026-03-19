<?php

namespace App\Policies;

use App\Models\Fiche;
use App\Models\User;

class FichePolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Fiche $fiche): bool
    {
        return $user->id === $fiche->user_id || $user->isAdmin() || $user->isCurator();
    }

    public function delete(User $user, Fiche $fiche): bool
    {
        return $user->id === $fiche->user_id || $user->isAdmin();
    }

    public function toggleDiamond(User $user, Fiche $fiche): bool
    {
        return $user->isAdmin() || $user->isCurator();
    }
}
