<?php

namespace App\Support;

class PreviewMode
{
    public const SESSION_KEY = 'preview.doelenpagina';

    /**
     * Staat de preview van de nieuwe doelenpagina aan voor deze bezoeker?
     *
     * Enkel de geheime link (`?preview=<token>`) zet dit aan; ook admins en
     * curatoren zien de preview niet zonder die link.
     */
    public static function doelenpagina(): bool
    {
        return session()->get(self::SESSION_KEY) === true;
    }
}
