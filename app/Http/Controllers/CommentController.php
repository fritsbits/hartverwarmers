<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Store a new comment for an activity.
     */
    public function store(Request $request, Activity $activity): RedirectResponse
    {
        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        Comment::create([
            'comment' => $validated['comment'],
            'user_id' => $request->user()->id,
            'commentable_type' => Activity::class,
            'commentable_id' => $activity->id,
        ]);

        return back()->with('status', 'Reactie geplaatst.');
    }
}
