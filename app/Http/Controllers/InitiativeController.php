<?php

namespace App\Http\Controllers;

use App\Models\Initiative;
use App\Services\DiamantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InitiativeController extends Controller
{
    public function index(DiamantService $diamant): View
    {
        $initiatives = Initiative::query()
            ->published()
            ->with(['tags' => fn ($q) => $q->where('type', 'goal')])
            ->withCount(['fiches' => fn ($q) => $q->published()])
            ->orderBy('title')
            ->get();

        $goals = collect($diamant->all())->map(fn (array $facet) => [
            'slug' => $facet['slug'],
            'tagSlug' => 'doel-'.$facet['slug'],
            'letter' => $facet['letter'],
            'keyword' => $facet['keyword'],
            'description' => $facet['ik_wil'],
        ])->values()->all();

        return view('initiatives.index', [
            'initiatives' => $initiatives,
            'goals' => $goals,
        ]);
    }

    public function show(Initiative $initiative, DiamantService $diamant): View
    {
        if (! $initiative->published) {
            abort(404);
        }

        $initiative->load([
            'tags',
            'fiches' => function ($query) {
                $query->published()
                    ->with(['tags', 'user', 'files'])
                    ->withCount(['likes as bookmarks_count' => fn ($q) => $q->where('type', 'bookmark')]);
            },
            'comments' => function ($query) {
                $query->with('user')->latest();
            },
        ]);

        // Related initiatives (shared tags, max 4)
        $tagIds = $initiative->tags->pluck('id');
        $relatedInitiatives = $tagIds->isNotEmpty()
            ? Initiative::query()
                ->published()
                ->where('id', '!=', $initiative->id)
                ->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds))
                ->with('tags')
                ->limit(4)
                ->get()
            : collect();

        $diamantQuote = config('diamant_quotes.'.$initiative->slug);

        $rawAnalyse = config('diamant_analyse.'.$initiative->slug);
        $diamantAnalyse = null;
        if (is_array($rawAnalyse)) {
            $diamantAnalyse = collect($rawAnalyse)->map(function (array $item) use ($diamant) {
                $facet = $diamant->findBySlug($item['facet']);

                return [
                    'facet' => $item['facet'],
                    'text' => $item['text'],
                    'keyword' => $facet['keyword'] ?? ucfirst($item['facet']),
                    'slug' => $facet['slug'] ?? $item['facet'],
                ];
            })->all();
        }

        return view('initiatives.show', [
            'initiative' => $initiative,
            'relatedInitiatives' => $relatedInitiatives,
            'diamantQuote' => $diamantQuote,
            'diamantAnalyse' => $diamantAnalyse,
        ]);
    }

    public function destroy(Initiative $initiative): RedirectResponse
    {
        $initiative->delete();

        return redirect()->route('initiatives.index')
            ->with('success', "Initiatief \"{$initiative->title}\" is verwijderd.");
    }
}
