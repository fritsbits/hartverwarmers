<?php

namespace App\Services\ContributorAnniversary;

use App\Models\Fiche;

class Payload
{
    public function __construct(
        public int $totalFiches,
        public int $totalBookmarks,
        public int $totalComments,
        public ?Fiche $spotlightFiche,
        public ?int $spotlightBookmarkCount,
    ) {}
}
