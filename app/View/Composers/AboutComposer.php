<?php

namespace App\View\Composers;

use App\Models\Fiche;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AboutComposer
{
    public function compose(View $view): void
    {
        $stats = Cache::remember('about_stats', 3600, function () {
            return [
                'fiches_count' => Fiche::count(),
                'contributors_count' => User::whereHas('fiches')->count(),
                'users_count' => User::count(),
            ];
        });

        $view->with('aboutStats', $stats);
    }
}
