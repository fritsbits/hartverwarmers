<?php

namespace App\Console\Commands;

use App\Mail\DiamondRotationSuggestionMail;
use App\Models\DiamondRotation;
use App\Models\Fiche;
use App\Services\DiamondRotation\CandidateFinder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class SendDiamondRotationSuggestion extends Command
{
    protected $signature = 'diamonds:send-rotation-suggestion {--force : Resend even if the suggestion mail already went out}';

    protected $description = 'Mail the admin the diamond-of-the-month suggestion for next month, with one-tap backup choices';

    public function handle(CandidateFinder $finder): int
    {
        $month = now()->addMonth()->startOfMonth();

        $rotation = DiamondRotation::forMonth($month)->first();

        if ($rotation?->suggestion_sent_at && ! $this->option('force')) {
            $this->info("Suggestion for {$month->isoFormat('YYYY-MM')} was already sent. Use --force to resend.");

            return Command::SUCCESS;
        }

        $candidates = $finder->candidates(4);

        if ($candidates->isEmpty()) {
            $this->sendNoCandidatesAlert($month);
            $this->warn('No eligible candidates — alerted the admin to award one manually.');

            return Command::SUCCESS;
        }

        $rotation ??= new DiamondRotation(['month' => $month->toDateString()]);

        if ($rotation->chosen_via !== 'admin' || ! $rotation->fiche_id) {
            $rotation->fiche_id = $candidates->first()->id;
            $rotation->chosen_via = 'auto';
        }

        $candidates = $this->pickFirst($rotation, $candidates);

        $rotation->suggested_fiche_ids = $candidates->pluck('id')->all();
        $rotation->suggestion_sent_at = now();
        $rotation->save();

        Mail::to($this->adminAddress())
            ->send(new DiamondRotationSuggestionMail($rotation, $candidates));

        $this->info("Suggestion for {$month->isoFormat('YYYY-MM')} sent: \"{$candidates->first()->title}\" plus {$candidates->slice(1)->count()} backups.");

        return Command::SUCCESS;
    }

    /**
     * The mail presents the first candidate as "what happens if you do
     * nothing", so an earlier admin pick must lead the list even when it
     * isn't the top-scoring candidate (e.g. on a --force resend).
     *
     * @param  Collection<int, Fiche>  $candidates
     * @return Collection<int, Fiche>
     */
    private function pickFirst(DiamondRotation $rotation, Collection $candidates): Collection
    {
        if (! $rotation->fiche_id || $rotation->fiche_id === $candidates->first()->id) {
            return $candidates;
        }

        $pick = $candidates->firstWhere('id', $rotation->fiche_id)
            ?? Fiche::query()
                ->published()
                ->where('has_diamond', false)
                ->with(['user', 'initiative'])
                ->withCount(['likes', 'comments'])
                ->find($rotation->fiche_id);

        if (! $pick) {
            return $candidates;
        }

        return collect([$pick])
            ->merge($candidates->reject(fn (Fiche $fiche) => $fiche->id === $pick->id))
            ->values();
    }

    private function sendNoCandidatesAlert(Carbon $month): void
    {
        $monthLabel = $month->locale('nl_BE')->isoFormat('MMMM YYYY');

        Mail::raw(
            "Er zijn momenteel geen kandidaten voor het diamantje van {$monthLabel} (gepubliceerde fiches zonder diamantje).\n\n"
            ."De automatische wissel op 1 {$month->locale('nl_BE')->isoFormat('MMMM')} kan daardoor niet doorgaan. Ken handmatig een diamantje toe via het fiche-overzicht, anders vermeldt de maandelijkse update twee maanden na elkaar hetzelfde diamantje.",
            function ($message) use ($monthLabel): void {
                $message->to($this->adminAddress())
                    ->subject("Geen kandidaten voor het diamantje van {$monthLabel}");
            }
        );
    }

    private function adminAddress(): string
    {
        return config('mail.admin_address') ?: config('mail.from.address');
    }
}
