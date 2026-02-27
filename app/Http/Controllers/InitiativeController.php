<?php

namespace App\Http\Controllers;

use App\Models\Initiative;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InitiativeController extends Controller
{
    public function index(Request $request): View
    {
        $query = Initiative::query()
            ->published()
            ->with('tags')
            ->withCount(['fiches' => fn ($q) => $q->published()]);

        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('tags.slug', $request->tag);
            });
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        $initiatives = $query->latest()->paginate(12);

        $filterTags = Tag::query()
            ->where('type', 'theme')
            ->orderBy('name')
            ->get()
            ->groupBy('type');

        return view('initiatives.index', [
            'initiatives' => $initiatives,
            'filterTags' => $filterTags,
            'selectedTag' => $request->tag,
            'search' => $request->search,
        ]);
    }

    public function show(Initiative $initiative): View
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

        return view('initiatives.show', [
            'initiative' => $initiative,
            'relatedInitiatives' => $relatedInitiatives,
        ]);
    }

    public function destroy(Initiative $initiative): RedirectResponse
    {
        $initiative->delete();

        return redirect()->route('initiatives.index')
            ->with('success', "Initiatief \"{$initiative->title}\" is verwijderd.");
    }
}
