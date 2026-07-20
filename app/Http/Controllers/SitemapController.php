<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use App\Services\ProductUpdates;
use Illuminate\Http\Response;

class SitemapController
{
    public function __invoke(): Response
    {
        $initiatives = Initiative::query()
            ->published()
            ->select('slug', 'updated_at')
            ->get();

        $fiches = Fiche::query()
            ->published()
            ->with('initiative:id,slug')
            ->select('id', 'slug', 'initiative_id', 'updated_at')
            ->get();

        $contributors = User::query()
            ->whereHas('fiches', fn ($q) => $q->published())
            ->select('id', 'updated_at')
            ->get();

        $updates = ProductUpdates::all();

        $xml = view('sitemap', compact('initiatives', 'fiches', 'contributors', 'updates'))->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
