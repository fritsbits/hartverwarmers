<?php

namespace App\Http\Controllers;

use App\Models\Elaboration;
use App\Models\Like;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function toggle(Request $request, Elaboration $elaboration): RedirectResponse
    {
        $user = $request->user();

        $bookmark = Like::where('user_id', $user->id)
            ->where('likeable_type', Elaboration::class)
            ->where('likeable_id', $elaboration->id)
            ->where('type', 'bookmark')
            ->first();

        if ($bookmark) {
            $bookmark->delete();
            $message = 'Uitwerking verwijderd uit bookmarks.';
        } else {
            Like::create([
                'user_id' => $user->id,
                'likeable_type' => Elaboration::class,
                'likeable_id' => $elaboration->id,
                'type' => 'bookmark',
            ]);
            $message = 'Uitwerking toegevoegd aan bookmarks.';
        }

        return back()->with('status', $message);
    }
}
