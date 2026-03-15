<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
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

        return view('downloads-and-bookmarks', [
            'downloads' => $downloads,
            'bookmarks' => $bookmarks,
            'isGuest' => false,
        ]);
    }
}
