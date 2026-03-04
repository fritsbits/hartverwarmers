<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
use App\Models\Initiative;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class FicheController extends Controller
{
    public function create(): View
    {
        return view('fiches.create');
    }

    public function edit(Fiche $fiche): View
    {
        Gate::authorize('update', $fiche);

        return view('fiches.edit', ['fiche' => $fiche]);
    }

    public function show(Initiative $initiative, Fiche $fiche): View
    {
        if (! $initiative->published || ! $fiche->published) {
            abort(404);
        }

        $fiche->load(['tags', 'user', 'files']);
        $fiche->loadCount([
            'comments',
            'likes as bookmarks_count' => fn ($q) => $q->where('type', 'bookmark'),
        ]);

        $goalTags = Feature::active('diamant-goals')
            ? $fiche->tags->where('type', 'goal')
            : collect();

        $otherFiches = Fiche::query()
            ->where('initiative_id', $initiative->id)
            ->where('id', '!=', $fiche->id)
            ->published()
            ->with(['user', 'tags'])
            ->take(6)
            ->get();

        return view('fiches.show', [
            'initiative' => $initiative,
            'fiche' => $fiche,
            'goalTags' => $goalTags,
            'otherFiches' => $otherFiches,
        ]);
    }

    public function downloadFiles(Initiative $initiative, Fiche $fiche): BinaryFileResponse|StreamedResponse
    {
        if (! $initiative->published || ! $fiche->published) {
            abort(404);
        }

        $files = $fiche->files;

        if ($files->isEmpty()) {
            abort(404);
        }

        $fiche->increment('download_count');

        if ($files->count() === 1) {
            $file = $files->first();

            return response()->download(
                Storage::disk('public')->path($file->path),
                $file->original_filename,
            );
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'fiche-zip-');
        $zip = new ZipArchive;
        $zip->open($tempPath, ZipArchive::OVERWRITE);

        foreach ($files as $file) {
            $zip->addFile(Storage::disk('public')->path($file->path), $file->original_filename);
        }

        $zip->close();

        $zipFilename = $fiche->slug.'-bestanden.zip';

        return response()->download($tempPath, $zipFilename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend();
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
