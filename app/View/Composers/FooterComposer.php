<?php

namespace App\View\Composers;

use App\Models\Elaboration;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class FooterComposer
{
    public function compose(View $view): void
    {
        $stats = Cache::remember('footer_stats', 3600, function () {
            return [
                'elaborations_count' => Elaboration::count(),
                'contributors_count' => User::count(),
                'organisations_count' => Organisation::count(),
            ];
        });

        $view->with('footerStats', $stats);
    }
}
