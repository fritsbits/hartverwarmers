<?php

namespace App\Jobs;

use App\Ai\Agents\FicheQualityAgent;
use App\Models\Fiche;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class AssessFicheQuality implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public Fiche $fiche)
    {
        $this->onConnection('database');
    }

    public function handle(): void
    {
        $fiche = $this->fiche->loadMissing(['initiative', 'tags']);
        $materials = $fiche->materials ?? [];

        $prompt = collect([
            "Titel: {$fiche->title}",
            'Beschrijving: '.Str::limit(strip_tags($fiche->description ?? ''), 500),
            $fiche->practical_tips ? 'Praktische tips: '.Str::limit(strip_tags($fiche->practical_tips), 300) : null,
            ! empty($materials['preparation']) ? 'Voorbereiding: '.Str::limit(strip_tags($materials['preparation']), 300) : null,
            ! empty($materials['inventory']) ? 'Benodigdheden: '.Str::limit(strip_tags($materials['inventory']), 300) : null,
            ! empty($materials['process']) ? 'Werkwijze: '.Str::limit(strip_tags($materials['process']), 500) : null,
            $fiche->target_audience ? 'Doelgroep: '.implode(', ', $fiche->target_audience) : null,
            $fiche->initiative ? "Initiatief: {$fiche->initiative->title}" : null,
            $fiche->initiative?->description ? 'Initiatiefbeschrijving: '.Str::limit(strip_tags($fiche->initiative->description), 300) : null,
            $fiche->tags->isNotEmpty() ? 'Tags: '.$fiche->tags->pluck('name')->implode(', ') : null,
        ])->filter()->implode("\n\n");

        $diamantGuidance = $fiche->initiative?->diamant_guidance;
        if ($diamantGuidance) {
            $activeGoals = collect($diamantGuidance)
                ->filter(fn ($goal) => $goal['active'] ?? false)
                ->map(fn ($goal, $key) => "- {$key}: ".($goal['description'] ?? ''))
                ->implode("\n");

            if ($activeGoals) {
                $prompt .= "\n\nActieve DIAMANT-doelen voor dit initiatief:\n".$activeGoals;
            }
        }

        try {
            $response = app(FicheQualityAgent::class)->prompt($prompt);

            $fiche->updateQuietly([
                'quality_score' => max(0, min(100, (int) $response['score'])),
                'quality_justification' => Str::limit((string) $response['justification'], 1000),
                'quality_assessed_at' => now(),
            ]);
        } catch (\Throwable) {
            $fiche->updateQuietly([
                'quality_assessed_at' => now(),
            ]);
        }
    }
}
