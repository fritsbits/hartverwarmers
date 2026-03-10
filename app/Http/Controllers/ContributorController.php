<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class ContributorController extends Controller
{
    public function index(): View
    {
        return view('contributors.index');
    }

    public function show(User $user): View
    {
        $user->load(['fiches' => function ($query) {
            $query->published()->with('initiative', 'tags', 'files');
        }]);

        $fichesByInitiative = $user->fiches->groupBy('initiative.title')->sortKeysDesc();

        $stats = [
            'fiches_count' => $user->fiches->count(),
            'kudos_total' => $user->fiches->sum('kudos_count'),
            'downloads_total' => $user->fiches->sum('download_count'),
        ];

        return view('contributors.show', [
            'contributor' => $user,
            'fichesByInitiative' => $fichesByInitiative,
            'stats' => $stats,
        ]);
    }
}
