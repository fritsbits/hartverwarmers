<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiamondRotation;
use App\Models\Fiche;
use App\Services\DiamondRotation\CandidateFinder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class AdminDiamondRotationController extends Controller
{
    public function index(CandidateFinder $finder): View
    {
        $currentRotation = DiamondRotation::forMonth(now()->startOfMonth())
            ->with(['fiche.user', 'fiche.initiative'])
            ->first();

        $nextMonth = now()->addMonth()->startOfMonth();
        $nextRotation = DiamondRotation::forMonth($nextMonth)
            ->with(['fiche.user', 'fiche.initiative'])
            ->first();

        $currentDiamond = Fiche::query()
            ->published()
            ->where('has_diamond', true)
            ->with(['user', 'initiative'])
            ->orderByDesc('diamond_awarded_at')
            ->orderByDesc('created_at')
            ->first();

        $history = DiamondRotation::query()
            ->whereNotNull('awarded_at')
            ->with(['fiche.user', 'fiche.initiative'])
            ->orderByDesc('month')
            ->limit(12)
            ->get();

        return view('admin.diamond-rotations', [
            'currentRotation' => $currentRotation,
            'currentDiamond' => $currentDiamond,
            'nextMonth' => $nextMonth,
            'nextRotation' => $nextRotation,
            'candidates' => $finder->candidates(8),
            'history' => $history,
        ]);
    }

    public function choose(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fiche_id' => ['required', 'integer', 'exists:fiches,id'],
        ]);

        $fiche = Fiche::query()
            ->published()
            ->where('has_diamond', false)
            ->findOrFail($validated['fiche_id']);

        $month = now()->addMonth()->startOfMonth();

        $rotation = DiamondRotation::forMonth($month)->first()
            ?? new DiamondRotation(['month' => $month->toDateString()]);

        $rotation->fiche_id = $fiche->id;
        $rotation->chosen_via = 'admin';
        $rotation->suggested_fiche_ids = array_values(array_unique([
            ...($rotation->suggested_fiche_ids ?? []),
            $fiche->id,
        ]));
        $rotation->save();

        return redirect()->route('admin.diamond-rotations')
            ->with('success', "\"{$fiche->title}\" wordt het diamantje van {$rotation->monthLabel()}.");
    }

    public function sendSuggestionMail(): RedirectResponse
    {
        Artisan::call('diamonds:send-rotation-suggestion', ['--force' => true]);

        return redirect()->route('admin.diamond-rotations')
            ->with('success', 'Suggestiemail opnieuw verstuurd naar het beheerdersadres.');
    }
}
