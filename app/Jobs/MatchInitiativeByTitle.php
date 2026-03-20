<?php

namespace App\Jobs;

use App\Services\FicheAiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MatchInitiativeByTitle implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $cacheKey,
        public string $title,
        public string $description,
    ) {}

    public function handle(): void
    {
        try {
            $this->updateStatus('matching');

            $aiService = app(FicheAiService::class);

            if (! $aiService->isAvailable()) {
                $this->updateStatus('done', ['matched_initiatives' => null, 'reason' => 'ai_unavailable']);

                return;
            }

            $result = $aiService->matchInitiatives($this->title, $this->description, null);

            $this->updateStatus('done', ['matched_initiatives' => $result]);
        } catch (\Throwable $e) {
            Log::warning('MatchInitiativeByTitle failed', ['error' => $e->getMessage()]);
            $this->updateStatus('failed', ['reason' => $e->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function updateStatus(string $step, array $extra = []): void
    {
        Cache::put(
            "fiche-initiative-match:{$this->cacheKey}",
            array_merge(['step' => $step, 'updated_at' => now()->timestamp], $extra),
            3600
        );
    }
}
