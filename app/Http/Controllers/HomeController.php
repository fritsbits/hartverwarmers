<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\ThemeOccurrence;
use App\Models\User;
use App\Services\DiamantService;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(DiamantService $diamant): View
    {
        $initiatives = Initiative::query()
            ->published()
            ->with(['tags' => fn ($q) => $q->where('type', 'goal')], 'creator')
            ->withCount(['fiches' => fn ($q) => $q->published()])
            ->latest()
            ->get();

        $inspiratieTitels = [
            'doen' => 'actief mee te doen',
            'inclusief' => 'iedereen mee te laten doen',
            'autonomie' => 'eigen regie te versterken',
            'mensgericht' => 'vanuit de persoon te vertrekken',
            'anderen' => 'verbinding te maken',
            'normalisatie' => 'het gewone leven te eren',
            'talent' => 'talent te laten schitteren',
        ];

        $goals = collect($diamant->all())->map(fn (array $facet) => [
            'slug' => $facet['slug'],
            'tagSlug' => 'doel-'.$facet['slug'],
            'letter' => $facet['letter'],
            'keyword' => $facet['keyword'],
            'inspiratie' => $inspiratieTitels[$facet['slug']] ?? $facet['keyword'],
        ])->values()->all();

        $goalCounts = Initiative::query()
            ->published()
            ->join('taggables', fn ($j) => $j->on('initiatives.id', 'taggables.taggable_id')
                ->where('taggables.taggable_type', Initiative::class))
            ->join('tags', fn ($j) => $j->on('tags.id', 'taggables.tag_id')
                ->where('tags.slug', 'like', 'doel-%'))
            ->groupBy('tags.slug')
            ->selectRaw('tags.slug, count(distinct initiatives.id) as count')
            ->pluck('count', 'slug');

        $eligibleGoals = collect($goals)->filter(fn ($g) => ($goalCounts[$g['tagSlug']] ?? 0) >= 3)->values()->all();
        $defaultGoal = count($eligibleGoals) > 0
            ? collect($eligibleGoals)->random()['tagSlug']
            : collect($goals)->first()['tagSlug'];

        $recentFiches = Fiche::query()
            ->published()
            ->with('initiative', 'user', 'tags', 'files')
            ->withCount('comments')
            ->latest()
            ->take(4)
            ->get();

        $recentDiamond = Fiche::query()
            ->published()
            ->where('has_diamond', true)
            ->with(['initiative', 'user', 'tags', 'files'])
            ->withCount('comments')
            ->latest()
            ->first();

        $diamondCount = Fiche::query()->published()->where('has_diamond', true)->count();
        $diamonds = $diamondCount >= 3
            ? Fiche::query()
                ->published()
                ->where('has_diamond', true)
                ->with(['user', 'initiative', 'tags', 'files'])
                ->withCount(['likes', 'comments'])
                ->inRandomOrder()
                ->limit(3)
                ->get()
            : collect();

        $stats = Cache::remember('home:stats', 300, fn () => [
            'fiches' => Fiche::published()->count(),
            'contributors' => User::whereHas('fiches')->count(),
            'organisations' => User::whereNotNull('organisation')->distinct('organisation')->count('organisation'),
            'initiatives' => Initiative::where('published', true)->count(),
        ]);

        $today = today();
        $upcomingThemes = Cache::remember(
            'home:upcoming-themes:'.$today->toDateString(),
            now()->addHour(),
            fn () => ThemeOccurrence::query()
                ->where(function ($q) use ($today) {
                    $q->where('start_date', '>=', $today)
                        ->orWhere(function ($q2) use ($today) {
                            $q2->where('start_date', '<', $today)
                                ->whereNotNull('end_date')
                                ->where('end_date', '>=', $today);
                        });
                })
                ->orderBy('start_date')
                ->with('theme')
                ->limit(3)
                ->get()
        );

        return view('home', [
            'initiatives' => $initiatives,
            'goals' => $eligibleGoals,
            'defaultGoal' => $defaultGoal,
            'recentFiches' => $recentFiches,
            'recentDiamond' => $recentDiamond,
            'diamonds' => $diamonds,
            'stats' => $stats,
            'upcomingThemes' => $upcomingThemes,
        ]);
    }
}
