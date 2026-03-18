<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
use Illuminate\View\View;

class DiamantjesController extends Controller
{
    public function __invoke(): View
    {
        $fiches = Fiche::query()
            ->where('has_diamond', true)
            ->where('published', true)
            ->with(['user', 'initiative', 'tags', 'files'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->get();

        return view('diamantjes.index', compact('fiches'));
    }
}
