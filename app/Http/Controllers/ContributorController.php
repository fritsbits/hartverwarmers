<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class ContributorController extends Controller
{
    public function index(): View
    {
        return view('contributors.index');
    }

    public function show(User $user): View
    {
        $user->load(['fiches' => function ($query) {
            $query->published()
                ->withCount([
                    'comments',
                    'likes as bookmarks_count' => fn ($q) => $q->where('type', 'bookmark'),
                ])
                ->with('initiative', 'tags', 'files');
        }]);

        $fiches = $user->fiches->sortByDesc('created_at')->values();

        $stats = [
            'fiches_count' => $fiches->count(),
            'kudos_total' => $fiches->sum('kudos_count'),
            'initiative_count' => $fiches->pluck('initiative_id')->unique()->count(),
        ];

        // Related contributors: users sharing the same initiatives
        $initiativeIds = $user->fiches->pluck('initiative_id')->unique()->filter();
        $relatedContributors = collect();

        if ($initiativeIds->isNotEmpty()) {
            $relatedContributors = User::query()
                ->where('id', '!=', $user->id)
                ->whereHas('fiches', function ($query) use ($initiativeIds) {
                    $query->published()->whereIn('initiative_id', $initiativeIds);
                })
                ->withCount(['fiches' => fn ($q) => $q->published()])
                ->with(['fiches' => fn ($q) => $q->published()->whereIn('initiative_id', $initiativeIds)->select('id', 'user_id', 'initiative_id')->with('initiative:id,title')])
                ->orderByDesc('fiches_count')
                ->limit(3)
                ->get()
                ->each(function ($related) use ($initiativeIds) {
                    $related->shared_initiatives = $related->fiches
                        ->whereIn('initiative_id', $initiativeIds)
                        ->pluck('initiative.title')
                        ->unique()
                        ->values();
                });
        }

        return view('contributors.show', [
            'contributor' => $user,
            'fiches' => $fiches,
            'stats' => $stats,
            'relatedContributors' => $relatedContributors,
        ]);
    }
}
