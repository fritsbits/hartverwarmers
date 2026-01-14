<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Like;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    /**
     * Toggle bookmark for an activity.
     */
    public function toggle(Request $request, Activity $activity): RedirectResponse
    {
        $user = $request->user();

        $bookmark = Like::where('user_id', $user->id)
            ->where('likeable_type', Activity::class)
            ->where('likeable_id', $activity->id)
            ->where('type', 'bookmark')
            ->first();

        if ($bookmark) {
            $bookmark->delete();
            $message = 'Activiteit verwijderd uit bookmarks.';
        } else {
            Like::create([
                'user_id' => $user->id,
                'likeable_type' => Activity::class,
                'likeable_id' => $activity->id,
                'type' => 'bookmark',
            ]);
            $message = 'Activiteit toegevoegd aan bookmarks.';
        }

        return back()->with('status', $message);
    }
}
