<?php

namespace App\Services\ContributorAnniversary;

use App\Models\User;

class Composer
{
    public function compose(User $user): Payload
    {
        $firstFiche = $user->fiches()
            ->where('published', true)
            ->orderBy('created_at')
            ->with(['tags' => fn ($q) => $q->where('type', 'theme'), 'initiative'])
            ->first();

        return new Payload(
            firstFicheTitle: $firstFiche?->title,
            firstFicheTheme: $firstFiche?->tags->first()?->name,
            firstFicheInitiativeName: $firstFiche?->initiative?->title,
            firstFicheInitiativeSlug: $firstFiche?->initiative?->slug,
        );
    }
}
