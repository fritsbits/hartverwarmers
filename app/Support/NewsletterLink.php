<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\URL;

class NewsletterLink
{
    public static function tracked(User $user, string $destination): string
    {
        return URL::signedRoute('newsletter.click', [
            'user' => $user->id,
            'to' => base64_encode($destination),
        ]);
    }
}
