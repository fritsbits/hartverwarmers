<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $activities = Activity::query()
            ->published()
            ->shared()
            ->with('interests')
            ->latest()
            ->take(6)
            ->get();

        return view('home', [
            'activities' => $activities,
        ]);
    }
}
