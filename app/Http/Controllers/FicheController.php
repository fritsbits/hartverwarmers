<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
use App\Models\Initiative;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FicheController extends Controller
{
    public function show(Initiative $initiative, Fiche $fiche): View
    {
        if (! $initiative->published || ! $fiche->published) {
            abort(404);
        }

        $fiche->load(['tags', 'user', 'files', 'comments.user']);
        $fiche->loadCount(['likes as bookmarks_count' => fn ($q) => $q->where('type', 'bookmark')]);

        $relatedFiches = Fiche::query()
            ->where('initiative_id', $initiative->id)
            ->where('id', '!=', $fiche->id)
            ->published()
            ->with('user', 'tags')
            ->take(4)
            ->get();

        return view('fiches.show', [
            'initiative' => $initiative,
            'fiche' => $fiche,
            'relatedFiches' => $relatedFiches,
        ]);
    }

    public function print(Initiative $initiative, Fiche $fiche): View
    {
        if (! $initiative->published || ! $fiche->published) {
            abort(404);
        }

        $fiche->load(['tags', 'user', 'files']);

        return view('fiches.print', [
            'initiative' => $initiative,
            'fiche' => $fiche,
        ]);
    }

    public function toggleDiamond(Initiative $initiative, Fiche $fiche): RedirectResponse
    {
        $fiche->update(['has_diamond' => ! $fiche->has_diamond]);

        $status = $fiche->has_diamond ? 'toegekend aan' : 'verwijderd van';

        return redirect()->route('fiches.show', [$initiative, $fiche])
            ->with('success', "Diamantje {$status} \"{$fiche->title}\".");
    }

    public function destroy(Initiative $initiative, Fiche $fiche): RedirectResponse
    {
        $fiche->delete();

        return redirect()->route('initiatives.show', $initiative)
            ->with('success', "Fiche \"{$fiche->title}\" is verwijderd.");
    }
}
