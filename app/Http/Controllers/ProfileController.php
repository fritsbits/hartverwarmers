<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Services\AvatarThumbnailService;
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

        return redirect()->route('profile.show')->with('toast', [
            'heading' => 'Profiel bijgewerkt',
            'text' => 'Je wijzigingen zijn opgeslagen.',
            'variant' => 'success',
        ]);
    }

    public function updateAvatar(Request $request, AvatarThumbnailService $thumbnailService): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();

        // Delete old avatar and its thumbnail
        if ($user->avatar_path) {
            $thumbnailService->delete($user->avatar_path);
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $thumbnailService->generate($path);
        $user->update(['avatar_path' => $path]);

        return redirect()->route('profile.show')->with('toast', [
            'heading' => 'Nieuwe foto!',
            'text' => 'Je profielfoto is bijgewerkt.',
            'variant' => 'success',
        ]);
    }

    public function deleteAvatar(Request $request, AvatarThumbnailService $thumbnailService): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            $thumbnailService->delete($user->avatar_path);
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

    public function notifications(Request $request): View
    {
        return view('profile.notifications', [
            'user' => $request->user(),
        ]);
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $request->validate([
            'notification_frequency' => ['required', 'in:daily,weekly,never'],
        ]);

        $user = $request->user();
        $user->notification_frequency = $request->input('notification_frequency');
        $user->notify_on_kudos_milestones = $request->boolean('notify_on_kudos_milestones');
        $user->notify_on_onboarding_emails = $request->boolean('notify_on_onboarding_emails');
        $user->save();

        return redirect()->route('profile.notifications')->with('toast', [
            'heading' => 'Meldingen bijgewerkt',
            'text' => 'Je meldingsvoorkeuren zijn opgeslagen.',
            'variant' => 'success',
        ]);
    }

    public function unsubscribe(Request $request): RedirectResponse
    {
        $user = User::findOrFail($request->query('user'));
        $type = $request->query('type', 'comments');

        match ($type) {
            'comments' => $user->notification_frequency = 'never',
            'kudos' => $user->notify_on_kudos_milestones = false,
            'onboarding' => $user->notify_on_onboarding_emails = false,
            default => abort(400, "Unknown unsubscribe type: {$type}"),
        };

        $user->save();

        $message = match ($type) {
            'comments' => 'Je ontvangt geen e-mails meer over reacties op je fiches.',
            'kudos' => 'Je ontvangt geen e-mails meer over kudos en bladwijzers.',
            'onboarding' => 'Je ontvangt geen tips en inspiratie meer.',
        };

        return redirect()->route('home')->with('toast', [
            'heading' => 'Uitgeschreven',
            'text' => $message,
            'variant' => 'success',
        ]);
    }
}
