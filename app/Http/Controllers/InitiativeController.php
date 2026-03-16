<?php

namespace App\Http\Controllers;

use App\Features\DiamantGoals;
use App\Models\Initiative;
use App\Services\DiamantService;
use App\Services\FicheInteractionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Pennant\Feature;

class InitiativeController extends Controller
{
    public function index(DiamantService $diamant): View
    {
        $sixtyDaysAgo = now()->subDays(60);
        $thirtyDaysAgo = now()->subDays(30);

        $initiatives = Initiative::query()
            ->published()
            ->with(['tags' => fn ($q) => $q->where('type', 'goal')])
            ->withCount(['fiches' => fn ($q) => $q->published()])
            ->selectRaw('initiatives.*')
            ->selectRaw('(SELECT MAX(f.created_at) FROM fiches f WHERE f.initiative_id = initiatives.id AND f.published = 1 AND f.deleted_at IS NULL) as latest_fiche_at')
            ->selectRaw('(SELECT COUNT(*) FROM fiches f WHERE f.initiative_id = initiatives.id AND f.published = 1 AND f.deleted_at IS NULL AND f.created_at >= ?) as recent_fiches_count', [$sixtyDaysAgo])
            ->with(['fiches' => fn ($q) => $q->published()
                ->select('id', 'initiative_id', 'user_id', 'title', 'slug', 'created_at')
                ->with('user:id,first_name,last_name,avatar_path')])
            ->orderBy('title')
            ->get();

        // Compute top contributors per initiative
        $initiatives->each(function (Initiative $initiative) {
            $initiative->topContributors = $initiative->fiches
                ->sortByDesc('created_at')
                ->pluck('user')
                ->filter()
                ->unique('id')
                ->take(3)
                ->values();
        });

        // Trending: initiative with most recent fiches (min 2)
        $trending = $initiatives
            ->where('recent_fiches_count', '>=', 2)
            ->sortByDesc('recent_fiches_count')
            ->first();

        $trendingFiches = [];
        if ($trending) {
            $trendingFiches = $trending->fiches
                ->sortByDesc('created_at')
                ->take(3)
                ->values()
                ->all();
        }

        // Random order for "Willekeurig" sort
        $randomOrder = $initiatives->pluck('id')->shuffle()->values()->all();

        // Needs-love initiative titles for callout
        $needsLoveInitiatives = $initiatives->filter(fn ($i) => $i->fiches_count < 3)
            ->sortByDesc('latest_fiche_at')
            ->take(3)
            ->map(fn ($i) => ['title' => $i->title, 'route' => route('initiatives.show', $i)])
            ->values()
            ->all();

        // Recent fiches grouped by initiative for editorial section
        $recentByInitiative = $initiatives
            ->filter(fn ($i) => $i->recent_fiches_count >= 1)
            ->sortByDesc('latest_fiche_at')
            ->take(5)
            ->mapWithKeys(fn ($i) => [
                $i->slug => [
                    'title' => $i->title,
                    'route' => route('initiatives.show', $i),
                    'fiches' => $i->fiches
                        ->filter(fn ($f) => $f->created_at >= $sixtyDaysAgo && $f->user)
                        ->sortByDesc('created_at')
                        ->take(3)
                        ->map(function ($f) use ($i) {
                            $ficheColors = config('fiche-icons.colors');
                            $ficheColor = $ficheColors[$f->id % count($ficheColors)];

                            $iconSvg = $f->icon
                                ? view('components.lucide-icon-svg', ['icon' => $f->icon])->render()
                                : '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>';

                            return [
                                'id' => $f->id,
                                'title' => $f->title,
                                'url' => route('fiches.show', [$i, $f]),
                                'user_name' => $f->user->full_name,
                                'icon_svg' => $iconSvg,
                                'icon_color_bg' => $ficheColor['bg'],
                                'icon_color_text' => $ficheColor['text'],
                                'time_ago' => $f->created_at->diffForHumans(),
                            ];
                        })
                        ->values()
                        ->all(),
                ],
            ]);

        $recentFiches = collect(); // kept for backward compat
        $recentlyActiveInitiatives = $recentByInitiative->map(fn ($item) => ['title' => $item['title'], 'route' => $item['route']])->values()->all();

        $goals = Feature::for(null)->active(DiamantGoals::class)
            ? collect($diamant->all())->map(fn (array $facet) => [
                'slug' => $facet['slug'],
                'tagSlug' => 'doel-'.$facet['slug'],
                'letter' => $facet['letter'],
                'keyword' => $facet['keyword'],
                'description' => $facet['ik_wil'],
            ])->values()->all()
            : [];

        return view('initiatives.index', [
            'initiatives' => $initiatives,
            'goals' => $goals,
            'trending' => $trending,
            'trendingFiches' => $trendingFiches,
            'needsLoveInitiatives' => $needsLoveInitiatives,
            'randomOrder' => $randomOrder,
            'recentlyActiveInitiatives' => $recentlyActiveInitiatives,
            'recentFiches' => $recentFiches,
            'recentByInitiative' => $recentByInitiative,
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

        $diamantQuote = null;
        $diamantAnalyse = null;

        if (Feature::for(null)->active(DiamantGoals::class)) {
            $diamantQuote = config('diamant_quotes.'.$initiative->slug);

            $rawAnalyse = config('diamant_analyse.'.$initiative->slug);
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
        }

        $ficheAlpineData = $initiative->fiches->map(fn ($fiche) => [
            'id' => $fiche->id,
            'title' => $fiche->title,
            'description' => Str::limit(strip_tags($fiche->description), 200),
            'kudosCount' => $fiche->kudos_count,
            'createdAt' => $fiche->created_at->timestamp,
        ])->values()->all();

        $randomOrder = $initiative->fiches->pluck('id')->shuffle()->values()->all();

        $ficheInteractions = app(FicheInteractionService::class)
            ->forUser(auth()->user(), $initiative->fiches->pluck('id'));

        return view('initiatives.show', [
            'initiative' => $initiative,
            'relatedInitiatives' => $relatedInitiatives,
            'diamantQuote' => $diamantQuote,
            'diamantAnalyse' => $diamantAnalyse,
            'ficheAlpineData' => $ficheAlpineData,
            'randomOrder' => $randomOrder,
            'ficheInteractions' => $ficheInteractions,
        ]);
    }

    public function destroy(Initiative $initiative): RedirectResponse
    {
        $initiative->delete();

        return redirect()->route('initiatives.index')
            ->with('success', "Initiatief \"{$initiative->title}\" is verwijderd.");
    }
}
