<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
use Illuminate\View\View;

class DiamantjesController extends Controller
{
    public function __invoke(): View
    {
        $fiches = Fiche::query()
            ->published()
            ->where('has_diamond', true)
            ->with(['user', 'initiative', 'tags', 'files'])
            ->withCount(['likes', 'comments'])
            ->orderByDesc('diamond_awarded_at')
            ->orderByDesc('created_at')
            ->get();

        return view('diamantjes.index', compact('fiches'));
    }
}
