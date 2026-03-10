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

        return view('contributors.show', [
            'contributor' => $user,
        ]);
    }
}
