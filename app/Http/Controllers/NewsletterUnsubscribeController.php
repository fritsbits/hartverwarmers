<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsletterUnsubscribeController extends Controller
{
    public function __invoke(Request $request, User $user): View
    {
        abort_unless($request->hasValidSignature(), 403);

        if (! $user->newsletter_unsubscribed_at) {
            $user->update(['newsletter_unsubscribed_at' => now()]);
        }

        return view('newsletter.unsubscribed', ['user' => $user]);
    }
}
