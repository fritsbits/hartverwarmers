<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile.
     */
    public function show(Request $request): View
    {
        return view('profile.show', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Display the user's bookmarked activities.
     */
    public function bookmarks(Request $request): View
    {
        $bookmarks = $request->user()
            ->bookmarks()
            ->with('likeable')
            ->latest()
            ->get()
            ->pluck('likeable')
            ->filter();

        return view('profile.bookmarks', [
            'activities' => $bookmarks,
        ]);
    }
}
