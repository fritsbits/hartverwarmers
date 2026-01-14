<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Interest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $query = Activity::query()
            ->published()
            ->shared()
            ->with('interests');

        // Filter by interest (include child interests of the selected domain)
        if ($request->filled('interest')) {
            $query->whereHas('interests', function ($q) use ($request) {
                $q->where('interests.id', $request->interest)
                  ->orWhere('interests.parent_id', $request->interest);
            });
        }

        // Filter by theme
        if ($request->filled('theme')) {
            $query->whereHas('themes', function ($q) use ($request) {
                $q->where('themes.id', $request->theme);
            });
        }

        $activities = $query->latest()->paginate(12);

        $domains = Interest::domains()->orderBy('name')->get();

        return view('activities.index', [
            'activities' => $activities,
            'domains' => $domains,
            'selectedInterest' => $request->interest,
        ]);
    }

    public function show(Activity $activity): View
    {
        // Only show published and shared activities
        if (!$activity->published || !$activity->shared) {
            abort(404);
        }

        $activity->load(['interests', 'comments.user', 'themes']);

        return view('activities.show', [
            'activity' => $activity,
        ]);
    }

    public function print(Activity $activity): View
    {
        // Only show published and shared activities
        if (!$activity->published || !$activity->shared) {
            abort(404);
        }

        return view('activities.print', [
            'activity' => $activity,
        ]);
    }
}
