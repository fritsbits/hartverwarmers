<?php

namespace App\Services\DiamondRotation;

use App\Models\Fiche;
use Illuminate\Support\Collection;

class CandidateFinder
{
    /**
     * Published fiches without a diamond, best first. Quality score is the
     * primary signal (NULLs sort last so unassessed fiches never outrank
     * assessed ones), community engagement breaks ties.
     *
     * @return Collection<int, Fiche>
     */
    public function candidates(int $limit = 4): Collection
    {
        return Fiche::query()
            ->published()
            ->where('has_diamond', false)
            ->with(['user', 'initiative'])
            ->withCount(['likes', 'comments'])
            ->orderByRaw('quality_score IS NULL')
            ->orderByDesc('quality_score')
            ->orderByDesc('likes_count')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
