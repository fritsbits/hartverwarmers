<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Comment;
use App\Models\Fiche;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('profile.show', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($user->email !== $validated['email']) {
            $validated['email_verified_at'] = null;
        }

        $user->fill($validated);
        $user->save();

        return redirect()->route('profile.show')->with('success', 'Profiel bijgewerkt.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();

        // Delete old avatar
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar_path' => $path]);

        return redirect()->route('profile.show')->with('success', 'Profielfoto bijgewerkt.');
    }

    public function deleteAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        return redirect()->route('profile.show')->with('success', 'Profielfoto verwijderd.');
    }

    public function security(Request $request): View
    {
        return view('profile.security', [
            'user' => $request->user(),
        ]);
    }

    public function fiches(Request $request): View
    {
        $user = $request->user();

        $fiches = $user->fiches()
            ->with('initiative')
            ->withCount([
                'comments',
                'files',
                'likes as bookmarks_count' => fn ($q) => $q->where('type', 'bookmark'),
            ])
            ->latest()
            ->get();

        $newCommentsCount = Comment::whereHasMorph('commentable', Fiche::class, fn ($q) => $q->where('user_id', $user->id))
            ->when($user->fiches_comments_seen_at, fn ($q) => $q->where('comments.created_at', '>', $user->fiches_comments_seen_at))
            ->count();

        $stats = [
            'total' => $fiches->count(),
            'published' => $fiches->where('published', true)->count(),
            'drafts' => $fiches->where('published', false)->count(),
            'downloads' => $fiches->sum('download_count'),
            'kudos' => $fiches->sum('kudos_count'),
            'comments' => $fiches->sum('comments_count'),
        ];

        $user->update(['fiches_comments_seen_at' => now()]);

        return view('profile.fiches', compact('fiches', 'stats', 'newCommentsCount'));
    }

    public function bookmarks(Request $request): View
    {
        $fiches = $request->user()
            ->bookmarks()
            ->with('likeable.initiative', 'likeable.tags', 'likeable.files')
            ->latest()
            ->get()
            ->pluck('likeable')
            ->filter();

        return view('profile.bookmarks', [
            'fiches' => $fiches,
        ]);
    }
}
