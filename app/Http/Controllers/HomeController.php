<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use App\Services\DiamantService;
use Illuminate\View\View;
use Laravel\Pennant\Feature;

class HomeController extends Controller
{
    public function __invoke(DiamantService $diamantService): View
    {
        $initiatives = Initiative::query()
            ->where('published', true)
            ->with('tags', 'creator')
            ->withCount(['fiches' => fn ($q) => $q->published()])
            ->latest()
            ->take(6)
            ->get();

        $recentFiches = Fiche::query()
            ->published()
            ->with('initiative', 'user', 'tags')
            ->latest()
            ->take(4)
            ->get();

        $stats = [
            'fiches' => Fiche::published()->count(),
            'contributors' => User::whereHas('fiches')->count(),
            'organisations' => User::whereNotNull('organisation')->distinct('organisation')->count('organisation'),
            'initiatives' => Initiative::where('published', true)->count(),
        ];

        $facets = [];
        $firstFacetSlug = null;
        $firstFacet = null;
        $goalInitiativeCounts = [];

        if (Feature::active('diamant-goals')) {
            $facets = $diamantService->all();
            $firstFacetSlug = array_key_first($facets);
            $firstFacet = $facets[$firstFacetSlug];

            // Count initiatives per goal
            foreach ($facets as $slug => $facet) {
                $goalInitiativeCounts[$slug] = Initiative::query()
                    ->where('published', true)
                    ->whereHas('tags', function ($q) use ($slug) {
                        $q->where('slug', 'doel-'.$slug);
                    })
                    ->count();
            }
        }

        return view('home', [
            'initiatives' => $initiatives,
            'recentFiches' => $recentFiches,
            'facets' => $facets,
            'firstFacetSlug' => $firstFacetSlug,
            'firstFacet' => $firstFacet,
            'goalInitiativeCounts' => $goalInitiativeCounts,
            'stats' => $stats,
        ]);
    }
}
