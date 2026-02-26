<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
use App\Models\Like;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function toggle(Request $request, Fiche $fiche): RedirectResponse
    {
        $user = $request->user();

        $bookmark = Like::where('user_id', $user->id)
            ->where('likeable_type', Fiche::class)
            ->where('likeable_id', $fiche->id)
            ->where('type', 'bookmark')
            ->first();

        if ($bookmark) {
            $bookmark->delete();
            $message = 'Fiche verwijderd uit favorieten.';
        } else {
            Like::create([
                'user_id' => $user->id,
                'likeable_type' => Fiche::class,
                'likeable_id' => $fiche->id,
                'type' => 'bookmark',
            ]);
            $message = 'Fiche toegevoegd aan favorieten.';
        }

        return back()->with('status', $message);
    }
}
