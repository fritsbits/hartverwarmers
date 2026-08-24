<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Okr\Objective;
use Illuminate\Http\RedirectResponse;

class AdminOkrController extends Controller
{
    public function archive(Objective $objective): RedirectResponse
    {
        $objective->update(['archived_at' => now()]);

        return redirect()
            ->route('admin.dashboard', ['tab' => 'overzicht'])
            ->with('success', "OKR '{$objective->title}' is gearchiveerd.");
    }

    public function unarchive(Objective $objective): RedirectResponse
    {
        $objective->update(['archived_at' => null]);

        return redirect()
            ->route('admin.dashboard', ['tab' => $objective->slug])
            ->with('success', "OKR '{$objective->title}' is opnieuw actief.");
    }
}
