<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\UserInteraction;
use App\Services\FicheInteractionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
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

        $otherFiches = Fiche::query()
            ->where('initiative_id', $initiative->id)
            ->where('id', '!=', $fiche->id)
            ->published()
            ->with(['user', 'tags', 'files'])
            ->take(6)
            ->get();

        if (auth()->check()) {
            UserInteraction::firstOrCreate([
                'user_id' => auth()->id(),
                'interactable_type' => Fiche::class,
                'interactable_id' => $fiche->id,
                'type' => 'view',
            ]);
        }

        $ficheInteractions = app(FicheInteractionService::class)
            ->forUser(auth()->user(), $otherFiches->pluck('id'));

        return view('fiches.show', [
            'initiative' => $initiative,
            'fiche' => $fiche,
            'otherFiches' => $otherFiches,
            'ficheInteractions' => $ficheInteractions,
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

        if (auth()->check()) {
            UserInteraction::firstOrCreate([
                'user_id' => auth()->id(),
                'interactable_type' => Fiche::class,
                'interactable_id' => $fiche->id,
                'type' => 'download',
            ]);
        }

        if ($files->count() === 1) {
            $file = $files->first();

            return response()->download(
                Storage::disk('public')->path($file->path),
                $file->original_filename,
            );
        }

        // Multi-file: serve pre-built ZIP or fall back to on-the-fly
        if ($fiche->zip_path && Storage::disk('public')->exists($fiche->zip_path)) {
            return response()->download(
                Storage::disk('public')->path($fiche->zip_path),
                $fiche->slug.'-bestanden.zip',
            );
        }

        // Fallback: generate on-the-fly (for fiches without pre-built ZIP)
        $tempPath = tempnam(sys_get_temp_dir(), 'fiche-zip-');
        $zip = new ZipArchive;
        $zip->open($tempPath, ZipArchive::OVERWRITE);

        foreach ($files as $file) {
            $zip->addFile(Storage::disk('public')->path($file->path), $file->original_filename);
        }

        $zip->close();

        return response()->download($tempPath, $fiche->slug.'-bestanden.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend();
    }

    public function toggleDiamond(Initiative $initiative, Fiche $fiche, Request $request): RedirectResponse
    {
        $fiche->update(['has_diamond' => ! $fiche->has_diamond]);

        $status = $fiche->has_diamond ? 'toegekend aan' : 'verwijderd van';

        return redirect()->to($request->input('_redirect', route('fiches.show', [$initiative, $fiche])))
            ->with('success', "Diamantje {$status} \"{$fiche->title}\".");
    }

    public function destroy(Initiative $initiative, Fiche $fiche): RedirectResponse
    {
        $fiche->delete();

        return redirect()->route('initiatives.show', $initiative)
            ->with('success', "Fiche \"{$fiche->title}\" is verwijderd.");
    }
}
