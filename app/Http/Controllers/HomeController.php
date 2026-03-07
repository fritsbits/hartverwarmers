<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $initiatives = Initiative::query()
            ->where('published', true)
            ->with('tags', 'creator')
            ->withCount(['fiches' => fn ($q) => $q->published()])
            ->latest()
            ->take(6)
            ->get();

        $recentFiches = Fiche::query()
            ->published()
            ->with('initiative', 'user', 'tags', 'files')
            ->withCount('comments')
            ->latest()
            ->take(4)
            ->get();

        $diamantFiche = Fiche::query()
            ->published()
            ->where('has_diamond', true)
            ->with('initiative', 'user', 'tags', 'files')
            ->withCount('comments')
            ->latest()
            ->first();

        $stats = Cache::remember('home:stats', 300, fn () => [
            'fiches' => Fiche::published()->count(),
            'contributors' => User::whereHas('fiches')->count(),
            'organisations' => User::whereNotNull('organisation')->distinct('organisation')->count('organisation'),
            'initiatives' => Initiative::where('published', true)->count(),
        ]);

        return view('home', [
            'initiatives' => $initiatives,
            'recentFiches' => $recentFiches,
            'diamantFiche' => $diamantFiche,
            'stats' => $stats,
        ]);
    }
}
