<?php

namespace App\Console\Commands;

use App\Models\DiamondRotation;
use App\Models\Fiche;
use App\Services\DiamondRotation\CandidateFinder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class RotateMonthlyDiamond extends Command
{
    protected $signature = 'diamonds:rotate';

    protected $description = 'Award this month\'s diamond: the admin\'s pick if one was made, otherwise the top suggestion';

    public function handle(CandidateFinder $finder): int
    {
        $month = now()->startOfMonth();

        $rotation = DiamondRotation::forMonth($month)->first();

        if ($rotation?->isAwarded()) {
            $this->info("Diamond for {$month->isoFormat('YYYY-MM')} was already awarded.");

            return Command::SUCCESS;
        }

        $fiche = $this->resolveFiche($rotation, $finder);

        if (! $fiche) {
            $this->sendNoCandidatesAlert($month);
            $this->warn('No eligible fiche to award — alerted the admin.');

            return Command::FAILURE;
        }

        $fiche->update([
            'has_diamond' => true,
            'diamond_awarded_at' => now(),
        ]);

        Cache::forget('home:recent-diamond');

        $rotation ??= new DiamondRotation(['month' => $month->toDateString()]);
        $rotation->chosen_via ??= 'auto';
        $rotation->fiche_id = $fiche->id;
        $rotation->awarded_at = now();
        $rotation->save();

        $this->info("Diamond for {$month->isoFormat('YYYY-MM')} awarded to \"{$fiche->title}\" ({$rotation->chosen_via}).");

        return Command::SUCCESS;
    }

    /**
     * The pick (or its backups) may have been unpublished, deleted, or awarded
     * a diamond by hand since the suggestion mail went out — fall through the
     * list, then to a fresh lookup, so the rotation still happens.
     */
    private function resolveFiche(?DiamondRotation $rotation, CandidateFinder $finder): ?Fiche
    {
        $candidateIds = array_unique(array_filter([
            $rotation?->fiche_id,
            ...($rotation?->suggested_fiche_ids ?? []),
        ]));

        foreach ($candidateIds as $id) {
            $fiche = Fiche::query()
                ->published()
                ->where('has_diamond', false)
                ->find($id);

            if ($fiche) {
                return $fiche;
            }
        }

        return $finder->candidates(1)->first();
    }

    private function sendNoCandidatesAlert(Carbon $month): void
    {
        $monthLabel = $month->locale('nl_BE')->isoFormat('MMMM YYYY');

        Mail::raw(
            "De automatische diamantje-wissel voor {$monthLabel} kon niet doorgaan: er is geen enkele gepubliceerde fiche zonder diamantje.\n\n"
            .'Ken handmatig een diamantje toe via het fiche-overzicht, anders vermeldt de maandelijkse update opnieuw het vorige diamantje.',
            function ($message) use ($monthLabel): void {
                $message->to(config('mail.admin_address') ?: config('mail.from.address'))
                    ->subject("Diamantje-wissel voor {$monthLabel} niet gelukt");
            }
        );
    }
}
