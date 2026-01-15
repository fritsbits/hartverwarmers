<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public function index(Request $request): View
    {
        $month = $request->input('maand', now()->month);
        $year = $request->input('jaar', now()->year);

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->addMonth()->endOfMonth();

        $themes = Theme::whereBetween('start', [$startDate, $endDate])
            ->orderBy('start')
            ->get()
            ->groupBy(fn($theme) => $theme->start->format('Y-m'));

        $previousDate = $startDate->copy()->subMonth();
        $nextDate = $startDate->copy()->addMonths(2);

        return view('themes.index', [
            'themesByMonth' => $themes,
            'startDate' => $startDate,
            'previousUrl' => route('themes.index', [
                'maand' => $previousDate->month,
                'jaar' => $previousDate->year,
            ]),
            'nextUrl' => route('themes.index', [
                'maand' => $nextDate->month,
                'jaar' => $nextDate->year,
            ]),
        ]);
    }
}
