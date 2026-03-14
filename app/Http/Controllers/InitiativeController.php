<?php

namespace App\Http\Controllers;

use App\Features\DiamantGoals;
use App\Models\Initiative;
use App\Services\DiamantService;
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

        // Interleaved "Ontdek" order
        $rich = $initiatives->filter(fn ($i) => $i->fiches_count >= 10)->sortByDesc('latest_fiche_at')->values();
        $growing = $initiatives->filter(fn ($i) => $i->fiches_count >= 3 && $i->fiches_count < 10)->sortByDesc('latest_fiche_at')->values();
        $needsLove = $initiatives->filter(fn ($i) => $i->fiches_count < 3)->sortByDesc('latest_fiche_at')->values();

        $discoverOrder = [];
        $maxLen = max($rich->count(), $growing->count(), $needsLove->count(), 1);
        for ($i = 0; $i < $maxLen; $i++) {
            if (isset($rich[$i])) {
                $discoverOrder[] = $rich[$i]->id;
            }
            if (isset($growing[$i])) {
                $discoverOrder[] = $growing[$i]->id;
            }
            if (isset($needsLove[$i])) {
                $discoverOrder[] = $needsLove[$i]->id;
            }
        }

        // Needs-love initiative titles for callout
        $needsLoveInitiatives = $needsLove->take(3)->map(fn ($i) => ['title' => $i->title, 'route' => route('initiatives.show', $i)])->values()->all();

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
                            $avatarColors = [
                                ['bg' => '#FDF3EE', 'text' => '#E8764B'],
                                ['bg' => '#E8F6F8', 'text' => '#3A9BA8'],
                                ['bg' => '#FEF6E0', 'text' => '#B08A22'],
                                ['bg' => '#F3E8F3', 'text' => '#9A5E98'],
                            ];
                            $color = $avatarColors[$f->user->id % 4];

                            return [
                                'id' => $f->id,
                                'title' => $f->title,
                                'url' => route('fiches.show', [$i, $f]),
                                'user_name' => $f->user->full_name,
                                'user_avatar' => $f->user->avatar_path ? $f->user->avatarUrl() : null,
                                'user_initial' => mb_strtoupper(mb_substr($f->user->first_name, 0, 1).mb_substr($f->user->last_name, 0, 1)),
                                'user_color_bg' => $color['bg'],
                                'user_color_text' => $color['text'],
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
            'discoverOrder' => $discoverOrder,
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

        return view('initiatives.show', [
            'initiative' => $initiative,
            'relatedInitiatives' => $relatedInitiatives,
            'diamantQuote' => $diamantQuote,
            'diamantAnalyse' => $diamantAnalyse,
            'ficheAlpineData' => $ficheAlpineData,
            'randomOrder' => $randomOrder,
        ]);
    }

    public function destroy(Initiative $initiative): RedirectResponse
    {
        $initiative->delete();

        return redirect()->route('initiatives.index')
            ->with('success', "Initiatief \"{$initiative->title}\" is verwijderd.");
    }
}
