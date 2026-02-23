<?php

namespace App\Http\Controllers;

use App\Models\Elaboration;
use App\Models\Initiative;
use App\Models\Organisation;
use App\Models\User;
use App\Services\DiamantService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(DiamantService $diamantService): View
    {
        $initiatives = Initiative::query()
            ->where('published', true)
            ->with('tags', 'creator')
            ->latest()
            ->take(6)
            ->get();

        $recentElaborations = Elaboration::query()
            ->published()
            ->with('initiative', 'user', 'tags')
            ->latest()
            ->take(4)
            ->get();

        $stats = [
            'elaborations' => Elaboration::published()->count(),
            'contributors' => User::whereHas('elaborations')->count(),
            'organisations' => Organisation::count(),
            'initiatives' => Initiative::where('published', true)->count(),
        ];

        $facets = $diamantService->all();
        $firstFacetSlug = array_key_first($facets);
        $firstFacet = $facets[$firstFacetSlug];

        // Count initiatives per goal
        $goalInitiativeCounts = [];
        foreach ($facets as $slug => $facet) {
            $goalInitiativeCounts[$slug] = Initiative::query()
                ->where('published', true)
                ->whereHas('tags', function ($q) use ($slug) {
                    $q->where('slug', 'doel-'.$slug);
                })
                ->count();
        }

        return view('home', [
            'initiatives' => $initiatives,
            'recentElaborations' => $recentElaborations,
            'facets' => $facets,
            'firstFacetSlug' => $firstFacetSlug,
            'firstFacet' => $firstFacet,
            'goalInitiativeCounts' => $goalInitiativeCounts,
            'stats' => $stats,
        ]);
    }
}
