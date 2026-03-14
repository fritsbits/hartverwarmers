<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FicheInteractionService;
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
                ->withCount('comments')
                ->with('initiative', 'tags', 'files');
        }]);

        $fichesByInitiative = $user->fiches
            ->groupBy('initiative.title')
            ->sortBy(fn ($fiches) => $fiches->min('created_at'));

        $stats = [
            'fiches_count' => $user->fiches->count(),
            'kudos_total' => $user->fiches->sum('kudos_count'),
        ];

        // Assign deterministic colors per initiative (0–5)
        $initiativeColors = $fichesByInitiative->keys()
            ->values()
            ->mapWithKeys(fn ($title, $index) => [$title => $index % 6]);

        // Dominant initiative color (for avatar placeholder)
        $dominantInitiative = $fichesByInitiative->map->count()->sortDesc()->keys()->first();
        $dominantColorIndex = $dominantInitiative ? ($initiativeColors[$dominantInitiative] ?? 0) : 0;

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

        $ficheInteractions = app(FicheInteractionService::class)
            ->forUser(auth()->user(), $user->fiches->pluck('id'));

        return view('contributors.show', [
            'contributor' => $user,
            'fichesByInitiative' => $fichesByInitiative,
            'stats' => $stats,
            'initiativeColors' => $initiativeColors,
            'relatedContributors' => $relatedContributors,
            'dominantColorIndex' => $dominantColorIndex,
            'ficheInteractions' => $ficheInteractions,
        ]);
    }
}
