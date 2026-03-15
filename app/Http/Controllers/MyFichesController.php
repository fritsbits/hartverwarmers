<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Fiche;
use Illuminate\View\View;

class MyFichesController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        if (! $user) {
            return view('my-fiches', [
                'fiches' => collect(),
                'stats' => null,
                'newCommentsCount' => 0,
                'isGuest' => true,
            ]);
        }

        $fiches = $user->fiches()
            ->with('initiative')
            ->withCount([
                'comments',
                'files',
                'likes as bookmarks_count' => fn ($q) => $q->where('type', 'bookmark'),
            ])
            ->latest()
            ->get();

        $newCommentsCount = Comment::whereHasMorph('commentable', Fiche::class, fn ($q) => $q->where('user_id', $user->id))
            ->when($user->fiches_comments_seen_at, fn ($q) => $q->where('comments.created_at', '>', $user->fiches_comments_seen_at))
            ->count();

        $stats = [
            'total' => $fiches->count(),
            'published' => $fiches->where('published', true)->count(),
            'drafts' => $fiches->where('published', false)->count(),
            'downloads' => $fiches->sum('download_count'),
            'kudos' => $fiches->sum('kudos_count'),
            'comments' => $fiches->sum('comments_count'),
        ];

        $user->update(['fiches_comments_seen_at' => now()]);

        return view('my-fiches', compact('fiches', 'stats', 'newCommentsCount') + ['isGuest' => false]);
    }
}
