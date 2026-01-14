<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public function index(): View
    {
        $themes = Theme::query()
            ->orderBy('start')
            ->get();

        return view('themes.index', [
            'themes' => $themes,
        ]);
    }

    public function show(Theme $theme): View
    {
        $theme->load('activities');

        return view('themes.show', [
            'theme' => $theme,
        ]);
    }
}
