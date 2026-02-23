<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ThemeController extends Controller
{
    public function index(): View
    {
        return view('themes.index');
    }
}
