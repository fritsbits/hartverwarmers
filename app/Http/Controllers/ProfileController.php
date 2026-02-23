<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('profile.show', [
            'user' => $request->user(),
        ]);
    }

    public function bookmarks(Request $request): View
    {
        $elaborations = $request->user()
            ->bookmarks()
            ->with('likeable.initiative', 'likeable.tags')
            ->latest()
            ->get()
            ->pluck('likeable')
            ->filter();

        return view('profile.bookmarks', [
            'elaborations' => $elaborations,
        ]);
    }
}
