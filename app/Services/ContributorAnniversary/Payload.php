<?php

namespace App\Services\ContributorAnniversary;

class Payload
{
    public function __construct(
        public ?string $firstFicheTitle,
        public ?string $firstFicheTheme,
        public ?string $firstFicheInitiativeName,
        public ?string $firstFicheInitiativeSlug,
    ) {}
}
