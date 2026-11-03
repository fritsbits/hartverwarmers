<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\InteractionEvent;
use App\Models\UserInteraction;
use App\Services\FicheInteractionService;
use App\Services\FicheStorage\DownloadBusy;
use App\Services\FicheStorage\FicheDownloader;
use App\Services\FicheStorage\ObjectMissing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

            InteractionEvent::create([
                'user_id' => auth()->id(),
                'type' => 'view',
                'fiche_id' => $fiche->id,
            ]);
        }

        $ficheInteractions = app(FicheInteractionService::class)
            ->forUser(auth()->user(), $otherFiches->pluck('id'));

        $showReturnVisitBanner = false;
        $lastDownloadDate = null;

        if (auth()->check() && auth()->id() !== $fiche->user_id) {
            $download = UserInteraction::query()
                ->where('user_id', auth()->id())
                ->where('interactable_type', Fiche::class)
                ->where('interactable_id', $fiche->id)
                ->where('type', 'download')
                ->latest('created_at')
                ->first();

            if ($download && ! $fiche->hasBeenThankedBy(auth()->user())) {
                $showReturnVisitBanner = true;
                $lastDownloadDate = $download->created_at;
            }
        }

        return view('fiches.show', [
            'initiative' => $initiative,
            'fiche' => $fiche,
            'otherFiches' => $otherFiches,
            'ficheInteractions' => $ficheInteractions,
            'showReturnVisitBanner' => $showReturnVisitBanner,
            'lastDownloadDate' => $lastDownloadDate,
        ]);
    }

    public function downloadFiles(Initiative $initiative, Fiche $fiche, FicheDownloader $downloader): BinaryFileResponse|RedirectResponse
    {
        if (! $initiative->published || ! $fiche->published) {
            abort(404);
        }

        $files = $fiche->files;

        if ($files->isEmpty()) {
            abort(404);
        }

        try {
            $response = $downloader->respond($fiche, $files);
        } catch (DownloadBusy) {
            return redirect()->route('fiches.show', [$initiative, $fiche])
                ->with('toast', ['text' => 'Het is even druk. Probeer het binnen een minuutje opnieuw.', 'variant' => 'warning']);
        } catch (ObjectMissing $e) {
            report($e);

            return redirect()->route('fiches.show', [$initiative, $fiche])
                ->with('toast', ['text' => 'Een bestand van deze fiche ontbreekt. We kijken het na.', 'variant' => 'danger']);
        }

        $fiche->increment('download_count');

        UserInteraction::firstOrCreate([
            'user_id' => auth()->id(),
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
        ]);

        InteractionEvent::create([
            'user_id' => auth()->id(),
            'type' => 'download',
            'fiche_id' => $fiche->id,
        ]);

        return $response;
    }

    public function downloadGate(Initiative $initiative, Fiche $fiche): RedirectResponse
    {
        if (! $initiative->published || ! $fiche->published) {
            abort(404);
        }

        if (auth()->check()) {
            return redirect()->route('fiches.download', [$initiative, $fiche]);
        }

        session(['url.intended' => route('fiches.show', [$initiative, $fiche])]);

        return redirect()->route('register');
    }

    public function toggleDiamond(Initiative $initiative, Fiche $fiche, Request $request): RedirectResponse
    {
        $awarding = ! $fiche->has_diamond;

        $fiche->update([
            'has_diamond' => $awarding,
            'diamond_awarded_at' => $awarding ? now() : null,
        ]);

        Cache::forget('home:recent-diamond');

        $status = $fiche->has_diamond ? 'toegekend aan' : 'verwijderd van';

        $redirectTo = $request->input('_redirect');

        if (! $redirectTo || ! str_starts_with($redirectTo, config('app.url'))) {
            $redirectTo = route('fiches.show', [$initiative, $fiche]);
        }

        return redirect()->to($redirectTo)
            ->with('success', "Diamantje {$status} \"{$fiche->title}\".");
    }
}
