<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Initiative;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Fiche $fiche): RedirectResponse
    {
        Comment::create([
            'body' => $request->validated('body'),
            'parent_id' => $request->validated('parent_id'),
            'user_id' => $request->user()->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
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
