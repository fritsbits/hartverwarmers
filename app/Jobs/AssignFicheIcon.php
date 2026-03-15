<?php

namespace App\Jobs;

use App\Ai\Agents\IconSelector;
use App\Models\Fiche;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class AssignFicheIcon implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public Fiche $fiche) {}

    public function handle(): void
    {
        $initiativeName = $this->fiche->initiative?->title ?? '';
        $prompt = "Initiatief: {$initiativeName}\nActiviteit: {$this->fiche->title}";

        if ($this->fiche->description) {
            $prompt .= "\nBeschrijving: ".Str::limit(strip_tags($this->fiche->description), 200);
        }

        $response = (new IconSelector)->prompt($prompt);
        $icon = trim((string) $response);

        $allowlist = config('fiche-icons.allowlist');

        if (! in_array($icon, $allowlist)) {
            $icon = 'file-text';
        }

        $this->fiche->updateQuietly(['icon' => $icon]);
    }
}
