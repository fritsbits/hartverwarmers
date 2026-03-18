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
        $fiche = $this->fiche->loadMissing(['initiative', 'tags', 'files']);
        $materials = $fiche->materials ?? [];

        $prompt = collect([
            "Titel: {$fiche->title}",
            'Beschrijving: '.strip_tags($fiche->description ?? ''),
            $fiche->practical_tips ? 'Praktische tips: '.strip_tags($fiche->practical_tips) : null,
            ! empty($materials['preparation']) ? 'Voorbereiding: '.strip_tags($materials['preparation']) : null,
            ! empty($materials['inventory']) ? 'Benodigdheden: '.strip_tags($materials['inventory']) : null,
            ! empty($materials['process']) ? 'Werkwijze: '.strip_tags($materials['process']) : null,
            $fiche->target_audience ? 'Doelgroep: '.implode(', ', $fiche->target_audience) : null,
            $fiche->initiative ? "Initiatief: {$fiche->initiative->title}" : null,
            $fiche->initiative?->description ? 'Initiatiefbeschrijving: '.strip_tags($fiche->initiative->description) : null,
            $fiche->tags->isNotEmpty() ? 'Tags: '.$fiche->tags->pluck('name')->implode(', ') : null,
        ])->filter()->implode("\n\n");

        $fileText = $fiche->files
            ->pluck('extracted_text')
            ->filter()
            ->implode("\n\n---\n\n");

        if ($fileText) {
            $prompt .= "\n\nInhoud van de bestanden (kan afgekapt zijn, beoordeel dit niet negatief):\n".Str::limit($fileText, 10000);
        }

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
                'quality_score' => max(0, min(100, (int) $response['quality_score'])),
                'quality_justification' => Str::limit((string) $response['quality_justification'], 1000),
                'presentation_score' => max(0, min(100, (int) $response['presentation_score'])),
                'presentation_justification' => Str::limit((string) $response['presentation_justification'], 1000),
                'quality_assessed_at' => now(),
            ]);
        } catch (\Throwable) {
            $fiche->updateQuietly([
                'quality_assessed_at' => now(),
            ]);
        }
    }
}
