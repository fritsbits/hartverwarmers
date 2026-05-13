<?php

namespace App\Services\MonthlyDigest;

use App\Models\Fiche;
use App\Models\ThemeOccurrence;
use Illuminate\Support\Carbon;

class Composer
{
    public function compose(Carbon $now): Payload
    {
        $themesWindowStart = $now->copy()->startOfDay();
        $themesWindowEnd = $now->copy()->addDays(30)->endOfDay();
        $ficheWindowStart = $now->copy()->subDays(30);

        $themes = ThemeOccurrence::query()
            ->whereBetween('start_date', [$themesWindowStart, $themesWindowEnd])
            ->orderBy('start_date')
            ->with(['theme' => fn ($q) => $q->withCount(['fiches' => fn ($q) => $q->published()])])
            ->limit(5)
            ->get();

        $upcomingThemeCount = ThemeOccurrence::query()
            ->whereBetween('start_date', [$themesWindowStart, $themesWindowEnd])
            ->count();

        $diamond = Fiche::query()
            ->published()
            ->where('has_diamond', true)
            ->with(['user', 'initiative'])
            ->orderByDesc('diamond_awarded_at')
            ->orderByDesc('created_at')
            ->first();

        $recentFiches = Fiche::query()
            ->published()
            ->where('created_at', '>=', $ficheWindowStart)
            ->with(['user', 'initiative'])
            ->latest()
            ->limit(6)
            ->get();

        $newFicheCount = Fiche::query()
            ->published()
            ->where('created_at', '>=', $ficheWindowStart)
            ->count();

        return new Payload(
            themes: $themes,
            diamond: $diamond,
            recentFiches: $recentFiches,
            upcomingThemeCount: $upcomingThemeCount,
            newFicheCount: $newFicheCount,
            sentAt: $now,
        );
    }
}
