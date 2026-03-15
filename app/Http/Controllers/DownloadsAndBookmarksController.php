<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DownloadsAndBookmarksController extends Controller
{
    public function __invoke(): View
    {
        return view('downloads-and-bookmarks');
    }
}
