<?php

namespace App\Http\Controllers;

use App\Models\DiamondRotation;
use App\Models\Fiche;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiamondRotationChoiceController extends Controller
{
    public function show(Request $request, DiamondRotation $rotation, Fiche $fiche): View
    {
        abort_unless($this->isCandidate($rotation, $fiche), 404);

        return view('diamantjes.rotation-choice', [
            'rotation' => $rotation,
            'fiche' => $fiche,
            'confirmUrl' => $request->fullUrl(),
        ]);
    }

    public function store(Request $request, DiamondRotation $rotation, Fiche $fiche): RedirectResponse
    {
        abort_unless($this->isCandidate($rotation, $fiche), 404);

        if (! $rotation->isAwarded()) {
            $rotation->update([
                'fiche_id' => $fiche->id,
                'chosen_via' => 'admin',
            ]);
        }

        return redirect()->to($request->fullUrl())->with('rotation-choice-saved', true);
    }

    /**
     * Only fiches that were in the suggestion mail (or the current pick)
     * can be chosen through this signed link.
     */
    private function isCandidate(DiamondRotation $rotation, Fiche $fiche): bool
    {
        return $rotation->fiche_id === $fiche->id
            || in_array($fiche->id, $rotation->suggested_fiche_ids ?? [], true);
    }
}
