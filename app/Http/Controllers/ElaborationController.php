<?php

namespace App\Http\Controllers;

use App\Models\Elaboration;
use App\Models\Initiative;
use Illuminate\View\View;

class ElaborationController extends Controller
{
    public function show(Initiative $initiative, Elaboration $elaboration): View
    {
        if (! $initiative->published || ! $elaboration->published) {
            abort(404);
        }

        $elaboration->load(['tags', 'user.organisation', 'files', 'comments.user']);
        $elaboration->loadCount('likes');

        $relatedElaborations = Elaboration::query()
            ->where('initiative_id', $initiative->id)
            ->where('id', '!=', $elaboration->id)
            ->published()
            ->with('user', 'tags')
            ->take(4)
            ->get();

        return view('elaborations.show', [
            'initiative' => $initiative,
            'elaboration' => $elaboration,
            'relatedElaborations' => $relatedElaborations,
        ]);
    }

    public function print(Initiative $initiative, Elaboration $elaboration): View
    {
        if (! $initiative->published || ! $elaboration->published) {
            abort(404);
        }

        $elaboration->load(['tags', 'user', 'files']);

        return view('elaborations.print', [
            'initiative' => $initiative,
            'elaboration' => $elaboration,
        ]);
    }
}
