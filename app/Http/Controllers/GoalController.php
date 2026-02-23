<?php

namespace App\Http\Controllers;

use App\Models\Initiative;
use App\Models\Tag;
use App\Services\DiamantService;
use Illuminate\View\View;

class GoalController extends Controller
{
    public function __construct(private DiamantService $diamantService) {}

    public function index(): View
    {
        $facets = $this->diamantService->all();

        $goalTags = Tag::query()
            ->where('type', 'goal')
            ->withCount(['initiatives' => function ($query) {
                $query->where('published', true);
            }])
            ->get()
            ->keyBy('slug');

        return view('goals.index', [
            'facets' => $facets,
            'goalTags' => $goalTags,
        ]);
    }

    public function show(string $facetSlug): View
    {
        $facet = $this->diamantService->findBySlug($facetSlug);

        if (! $facet) {
            abort(404);
        }

        $goalTag = Tag::query()
            ->where('type', 'goal')
            ->where('slug', 'doel-'.$facetSlug)
            ->first();

        $initiatives = collect();
        $facetInitiativeCount = 0;

        if ($goalTag) {
            $initiatives = $goalTag->initiatives()
                ->where('published', true)
                ->with('tags', 'creator')
                ->latest()
                ->limit(6)
                ->get();

            $facetInitiativeCount = $goalTag->initiatives()->where('published', true)->count();
        }

        $totalInitiativeCount = Initiative::where('published', true)->count();

        $allFacets = $this->diamantService->all();

        return view('goals.show', [
            'facet' => $facet,
            'initiatives' => $initiatives,
            'allFacets' => $allFacets,
            'totalInitiativeCount' => $totalInitiativeCount,
            'facetInitiativeCount' => $facetInitiativeCount,
        ]);
    }
}
