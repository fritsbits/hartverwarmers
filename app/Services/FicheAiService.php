<?php

namespace App\Services;

use App\Ai\Agents\AnalyzeFileContentAgent;
use App\Ai\Agents\MatchInitiativeAgent;
use App\Models\Initiative;
use Illuminate\Support\Facades\Log;
use Laravel\Pulse\Facades\Pulse;

class FicheAiService
{
    /** @var int Maximum combined text length sent to LLM */
    public const MAX_TEXT_CHARS = 20_000;

    public function isAvailable(): bool
    {
        return ! empty(config('ai.providers.anthropic.key'));
    }

    /**
     * @param  array<string>  $texts  Extracted texts from uploaded files
     * @return array{suggested_title: string, description: string, preparation: string, inventory: string, process: string, duration_estimate: string, group_size_estimate: string, suggested_themes: array, suggested_goals: array, suggested_target_audience: array, _meta: array}|null
     */
    public function analyzeFiles(array $texts, string $title, string $description): ?array
    {
        if (! $this->isAvailable() || empty($texts)) {
            return null;
        }

        try {
            $combinedText = implode("\n\n---\n\n", array_filter($texts));

            if (mb_strlen($combinedText) > self::MAX_TEXT_CHARS) {
                $combinedText = mb_substr($combinedText, 0, self::MAX_TEXT_CHARS);
            }

            $prompt = "Analyseer de volgende activiteit.\n\n";
            $prompt .= "Titel: {$title}\n";
            $prompt .= "Beschrijving: {$description}\n\n";
            $prompt .= "Inhoud van de bestanden:\n{$combinedText}";

            $start = microtime(true);
            $response = (new AnalyzeFileContentAgent)->prompt($prompt);
            $elapsed = round(microtime(true) - $start, 2);

            return [
                'suggested_title' => $response['suggested_title'] ?? '',
                'description' => $response['description'] ?? '',
                'preparation' => $response['preparation'] ?? '',
                'inventory' => $response['inventory'] ?? '',
                'process' => $response['process'] ?? '',
                'duration_estimate' => $response['duration_estimate'] ?? '',
                'group_size_estimate' => $response['group_size_estimate'] ?? '',
                'suggested_themes' => $response['suggested_themes'] ?? [],
                'suggested_goals' => $response['suggested_goals'] ?? [],
                'suggested_target_audience' => $response['suggested_target_audience'] ?? [],
                '_meta' => self::extractMeta($response, 'AnalyzeFileContentAgent', $elapsed),
            ];
        } catch (\Throwable $e) {
            Log::warning('FicheAiService::analyzeFiles failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array{matched_initiative_ids: array, match_reasons: array, _meta: array}|null
     */
    public function matchInitiatives(string $title, string $description, ?string $aiDescription): ?array
    {
        if (! $this->isAvailable()) {
            return null;
        }

        try {
            $initiatives = Initiative::query()
                ->published()
                ->select(['id', 'title', 'description'])
                ->get();

            if ($initiatives->isEmpty()) {
                return null;
            }

            $initiativeList = $initiatives->map(function ($i) {
                return "ID: {$i->id} | Titel: {$i->title} | Beschrijving: {$i->description}";
            })->implode("\n");

            $prompt = "Koppel deze fiche aan de beste initiatieven.\n\n";
            $prompt .= "Fiche titel: {$title}\n";
            $prompt .= "Fiche beschrijving: {$description}\n";
            if ($aiDescription) {
                $prompt .= "Samenvatting bestanden: {$aiDescription}\n";
            }
            $prompt .= "\nBeschikbare initiatieven:\n{$initiativeList}";

            $start = microtime(true);
            $response = (new MatchInitiativeAgent)->prompt($prompt);
            $elapsed = round(microtime(true) - $start, 2);

            return [
                'matched_initiative_ids' => $response['matched_initiative_ids'] ?? [],
                'match_reasons' => $response['match_reasons'] ?? [],
                '_meta' => self::extractMeta($response, 'MatchInitiativeAgent', $elapsed, [
                    'initiative_count' => $initiatives->count(),
                ]),
            ];
        } catch (\Throwable $e) {
            Log::warning('FicheAiService::matchInitiatives failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Extract timing, token, and model metadata from an agent response.
     *
     * @return array{agent: string, model: string|null, provider: string|null, input_tokens: int, output_tokens: int, elapsed_seconds: float, estimated_cost: float}
     */
    private static function extractMeta(mixed $response, string $agentName, float $elapsed, array $extra = []): array
    {
        $inputTokens = $response->usage->promptTokens ?? 0;
        $outputTokens = $response->usage->completionTokens ?? 0;
        $model = $response->meta->model ?? null;
        $provider = $response->meta->provider ?? null;
        $cost = self::estimateCost($model, $inputTokens, $outputTokens);
        $totalTokens = $inputTokens + $outputTokens;

        Pulse::record('ai_agent_call', $agentName, $cost)->sum()->count();
        Pulse::record('ai_agent_tokens', $agentName, $totalTokens)->sum();
        Pulse::record('ai_agent_duration', $agentName, (int) ($elapsed * 1000))->avg()->max();

        return array_merge([
            'agent' => $agentName,
            'model' => $model,
            'provider' => $provider,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'elapsed_seconds' => $elapsed,
            'estimated_cost' => $cost,
        ], $extra);
    }

    /**
     * Estimate cost in USD based on model and token counts.
     */
    private static function estimateCost(?string $model, int $inputTokens, int $outputTokens): float
    {
        // Haiku 4.5 pricing per million tokens
        $inputRate = 1.00;
        $outputRate = 5.00;

        if ($model && str_contains($model, 'sonnet')) {
            $inputRate = 3.00;
            $outputRate = 15.00;
        } elseif ($model && str_contains($model, 'opus')) {
            $inputRate = 15.00;
            $outputRate = 75.00;
        }

        return round(($inputTokens * $inputRate + $outputTokens * $outputRate) / 1_000_000, 4);
    }
}
