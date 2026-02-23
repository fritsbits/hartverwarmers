<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Elaboration;
use App\Models\Initiative;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Elaboration $elaboration): RedirectResponse
    {
        $validated = $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        Comment::create([
            'body' => $validated['body'],
            'user_id' => $request->user()->id,
            'commentable_type' => Elaboration::class,
            'commentable_id' => $elaboration->id,
        ]);

        return back()->with('status', 'Reactie geplaatst.');
    }

    public function storeForInitiative(Request $request, Initiative $initiative): RedirectResponse
    {
        $validated = $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        Comment::create([
            'body' => $validated['body'],
            'user_id' => $request->user()->id,
            'commentable_type' => Initiative::class,
            'commentable_id' => $initiative->id,
        ]);

        return back()->with('status', 'Reactie geplaatst.');
    }
}
