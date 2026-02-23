<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class ContributorController extends Controller
{
    public function index(): View
    {
        $contributors = User::query()
            ->has('elaborations')
            ->with('organisation')
            ->orderBy('name')
            ->get();

        return view('contributors.index', [
            'contributors' => $contributors,
        ]);
    }

    public function show(User $user): View
    {
        $user->load(['organisation', 'elaborations' => function ($query) {
            $query->published()->with('initiative', 'tags');
        }]);

        return view('contributors.show', [
            'contributor' => $user,
        ]);
    }
}
