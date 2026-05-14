<?php

namespace App\Services\ContributorAnniversary;

use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Like;
use App\Models\User;

class Composer
{
    public function compose(User $user): Payload
    {
        $publishedFicheIds = $user->fiches()->where('published', true)->pluck('id');

        $totalFiches = $publishedFicheIds->count();

        $totalBookmarks = Like::query()
            ->where('type', 'bookmark')
            ->where('likeable_type', Fiche::class)
            ->whereIn('likeable_id', $publishedFicheIds)
            ->count();

        $totalComments = Comment::query()
            ->where('commentable_type', Fiche::class)
            ->whereIn('commentable_id', $publishedFicheIds)
            ->count();

        $spotlight = Fiche::query()
            ->where('user_id', $user->id)
            ->where('published', true)
            ->withCount(['likes as bookmark_count' => fn ($q) => $q->where('type', 'bookmark')])
            ->whereHas('likes', fn ($q) => $q->where('type', 'bookmark'))
            ->orderByDesc('bookmark_count')
            ->orderByDesc('created_at')
            ->with('initiative')
            ->first();

        return new Payload(
            totalFiches: $totalFiches,
            totalBookmarks: $totalBookmarks,
            totalComments: $totalComments,
            spotlightFiche: $spotlight,
            spotlightBookmarkCount: $spotlight?->bookmark_count,
        );
    }
}
