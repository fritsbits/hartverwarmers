<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewsletterClickController extends Controller
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        $user->forceFill(['last_visited_at' => now()])->saveQuietly();

        $encoded = $request->query('to');
        $destination = $encoded ? base64_decode((string) $encoded, true) : null;

        if (! $destination || ! str_starts_with($destination, config('app.url'))) {
            return redirect()->route('home');
        }

        return redirect()->away($destination);
    }
}
