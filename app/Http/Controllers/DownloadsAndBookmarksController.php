<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Like;
use App\Models\UserInteraction;
use Illuminate\View\View;

class DownloadsAndBookmarksController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        if (! $user) {
            return view('downloads-and-bookmarks', [
                'downloads' => collect(),
                'bookmarks' => collect(),
                'thankedFicheIds' => collect(),
                'outstandingThanksCount' => 0,
                'isGuest' => true,
            ]);
        }

        $downloads = UserInteraction::where('user_id', $user->id)
            ->where('type', 'download')
            ->where('interactable_type', Fiche::class)
            ->whereHas('interactable')
            ->with('interactable.initiative', 'interactable.user')
            ->latest()
            ->get()
            ->pluck('interactable');

        $bookmarks = $user->bookmarks()
            ->whereHas('likeable')
            ->with('likeable.initiative', 'likeable.user')
            ->latest()
            ->get()
            ->pluck('likeable');

        $downloadedIds = $downloads->pluck('id');

        $thankedViaKudos = Like::query()
            ->where('user_id', $user->id)
            ->where('likeable_type', Fiche::class)
            ->whereIn('likeable_id', $downloadedIds)
            ->where('type', 'kudos')
            ->where('count', '>', 0)
            ->pluck('likeable_id');

        $thankedViaComment = Comment::query()
            ->where('user_id', $user->id)
            ->where('commentable_type', Fiche::class)
            ->whereIn('commentable_id', $downloadedIds)
            ->whereNull('deleted_at')
            ->pluck('commentable_id');

        $thankedFicheIds = $thankedViaKudos->merge($thankedViaComment)->unique()->values();
        $outstandingThanksCount = $downloads->count() - $thankedFicheIds->count();

        return view('downloads-and-bookmarks', [
            'downloads' => $downloads,
            'bookmarks' => $bookmarks,
            'thankedFicheIds' => $thankedFicheIds,
            'outstandingThanksCount' => max(0, $outstandingThanksCount),
            'isGuest' => false,
        ]);
    }
}
