<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Services\FicheAiService;
use Illuminate\Console\Command;

class AnalyzeFileCommand extends Command
{
    protected $signature = 'ai:analyze-file
        {--file= : The file ID to analyze}
        {--title= : Optional fiche title}
        {--description= : Optional fiche description}';

    protected $description = 'Run the AI analysis pipeline on a file and display timing/token metrics';

    public function handle(FicheAiService $aiService): int
    {
        if (! $aiService->isAvailable()) {
            $this->error('AI service is not available. Check your ANTHROPIC_API_KEY.');

            return self::FAILURE;
        }

        $fileId = $this->option('file');
        if (! $fileId) {
            $this->error('Please provide a --file ID.');

            return self::FAILURE;
        }

        $file = File::find($fileId);
        if (! $file) {
            $this->error("File #{$fileId} not found.");

            return self::FAILURE;
        }

        if (! $file->extracted_text) {
            $this->error("File #{$fileId} has no extracted text. Run text extraction first.");

            return self::FAILURE;
        }

        $title = $this->option('title') ?? $file->original_filename;
        $description = $this->option('description') ?? '';
        $textChars = mb_strlen($file->extracted_text);

        $this->info("Analyzing file #{$fileId}: {$file->original_filename}");
        $this->info("Text length: {$textChars} chars");
        $this->newLine();

        $rows = [];
        $totalInput = 0;
        $totalOutput = 0;
        $totalTime = 0.0;
        $totalCost = 0.0;
        $initiativeCount = 0;

        // Call 1: Analyze file content
        $this->info('Running AnalyzeFileContentAgent...');
        $analysis = $aiService->analyzeFiles([$file->extracted_text], $title, $description);

        if ($analysis && isset($analysis['_meta'])) {
            $meta = $analysis['_meta'];
            $rows[] = $this->formatRow($meta);
            $totalInput += $meta['input_tokens'];
            $totalOutput += $meta['output_tokens'];
            $totalTime += $meta['elapsed_seconds'];
            $totalCost += $meta['estimated_cost'];
        } else {
            $this->warn('AnalyzeFileContentAgent returned no results.');
        }

        // Call 2: Match initiatives
        $this->info('Running MatchInitiativeAgent...');
        $summary = $analysis['summary'] ?? null;
        $match = $aiService->matchInitiatives($title, $description, $summary);

        if ($match && isset($match['_meta'])) {
            $meta = $match['_meta'];
            $initiativeCount = $meta['initiative_count'] ?? 0;
            $rows[] = $this->formatRow($meta);
            $totalInput += $meta['input_tokens'];
            $totalOutput += $meta['output_tokens'];
            $totalTime += $meta['elapsed_seconds'];
            $totalCost += $meta['estimated_cost'];
        } elseif ($match === null) {
            $this->warn('MatchInitiativeAgent returned no results (no published initiatives?).');
        }

        $this->newLine();

        $this->table(
            ['Agent', 'Model', 'Input', 'Output', 'Time(s)', 'Cost'],
            array_merge($rows, [[
                '<fg=white;options=bold>TOTAL</>',
                '',
                number_format($totalInput),
                number_format($totalOutput),
                number_format($totalTime, 1),
                '$'.number_format($totalCost, 3),
            ]])
        );

        $this->newLine();
        $this->info("Input text: {$textChars} chars | Initiatives: {$initiativeCount}");

        if ($analysis) {
            $this->newLine();
            $this->info('Summary: '.($analysis['summary'] ?: '(empty)'));
            $this->info('Goals: '.implode(', ', $analysis['suggested_goals'] ?? []));
            $this->info('Themes: '.implode(', ', $analysis['suggested_themes'] ?? []));
            $this->info('Duration: '.($analysis['duration_estimate'] ?: '(empty)'));
            $this->info('Group size: '.($analysis['group_size_estimate'] ?: '(empty)'));
        }

        if ($match && ! empty($match['matched_initiative_ids'])) {
            $this->newLine();
            $this->info('Matched initiatives:');
            foreach ($match['matched_initiative_ids'] as $i => $id) {
                $reason = $match['match_reasons'][$i] ?? '';
                $this->line("  #{$id}: {$reason}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string>
     */
    private function formatRow(array $meta): array
    {
        return [
            $meta['agent'],
            $meta['model'] ?? 'unknown',
            number_format($meta['input_tokens']),
            number_format($meta['output_tokens']),
            number_format($meta['elapsed_seconds'], 1),
            '$'.number_format($meta['estimated_cost'], 3),
        ];
    }
}
