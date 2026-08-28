<?php

namespace App\Support;

use App\Features\DoelenpaginaPreview;
use Illuminate\Support\Facades\Auth;
use Laravel\Pennant\Feature;

class PreviewMode
{
    public const SESSION_KEY = 'preview.doelenpagina';

    /**
     * Staat de preview van de nieuwe doelenpagina aan voor deze bezoeker?
     */
    public static function doelenpagina(): bool
    {
        if (session()->get(self::SESSION_KEY) === true) {
            return true;
        }

        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return Feature::for($user)->active(DoelenpaginaPreview::class);
    }
}
