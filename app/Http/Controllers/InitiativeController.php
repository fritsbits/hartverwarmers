<?php

namespace App\Http\Controllers;

use App\Models\Initiative;
use App\Models\Tag;
use App\Services\DiamantService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InitiativeController extends Controller
{
    public function __construct(private DiamantService $diamantService) {}

    public function index(Request $request): View
    {
        $query = Initiative::query()
            ->published()
            ->with('tags', 'creator');

        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('tags.slug', $request->tag);
            });
        }

        $initiatives = $query->latest()->paginate(12);

        $tagTypes = ['interest', 'guidance'];
        $filterTags = Tag::query()
            ->whereIn('type', $tagTypes)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->groupBy('type');

        return view('initiatives.index', [
            'initiatives' => $initiatives,
            'filterTags' => $filterTags,
            'selectedTag' => $request->tag,
        ]);
    }

    public function show(Initiative $initiative): View
    {
        if (! $initiative->published) {
            abort(404);
        }

        $initiative->load([
            'tags',
            'creator',
            'elaborations' => function ($query) {
                $query->published()
                    ->with(['tags', 'user.organisation', 'files'])
                    ->withCount(['likes' => fn ($q) => $q->where('type', 'like')]);
            },
            'comments' => function ($query) {
                $query->with('user.organisation')->latest();
            },
        ]);

        // DIAMANT profile: merge config facets with initiative's guidance JSON
        $diamantProfile = collect($this->diamantService->all())->map(function ($facet) use ($initiative) {
            $guidance = $initiative->diamant_guidance[$facet['slug']] ?? null;

            return [
                ...$facet,
                'active' => $guidance['active'] ?? false,
                'initiative_description' => $guidance['description'] ?? null,
                'initiative_guidance' => $guidance['guidance'] ?? null,
            ];
        });

        // Social proof stats
        $contributorsCount = $initiative->elaborations->pluck('user_id')->unique()->count();

        // Related initiatives (shared tags, max 4)
        $tagIds = $initiative->tags->pluck('id');
        $relatedInitiatives = $tagIds->isNotEmpty()
            ? Initiative::query()
                ->published()
                ->where('id', '!=', $initiative->id)
                ->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds))
                ->with(['tags', 'creator'])
                ->limit(4)
                ->get()
            : collect();

        return view('initiatives.show', [
            'initiative' => $initiative,
            'diamantProfile' => $diamantProfile,
            'contributorsCount' => $contributorsCount,
            'relatedInitiatives' => $relatedInitiatives,
        ]);
    }
}
