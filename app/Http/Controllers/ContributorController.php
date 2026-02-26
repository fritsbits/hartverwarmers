<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class ContributorController extends Controller
{
    public function index(): View
    {
        $contributors = User::query()
            ->has('fiches')
            ->orderBy('last_name')
            ->get();

        return view('contributors.index', [
            'contributors' => $contributors,
        ]);
    }

    public function show(User $user): View
    {
        $user->load(['fiches' => function ($query) {
            $query->published()->with('initiative', 'tags');
        }]);

        return view('contributors.show', [
            'contributor' => $user,
        ]);
    }
}
