<?php

namespace App\Console\Commands;

use App\Models\Fiche;
use App\Services\FicheAiService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillSuggestions extends Command
{
    protected $signature = 'fiches:backfill-suggestions {--limit=10} {--dry-run}';

    protected $description = 'Generate AI suggestions for existing fiches using their extracted file text';

    public function handle(FicheAiService $aiService): int
    {
        $query = Fiche::query()
            ->published()
            ->whereNull('ai_suggestions')
            ->whereHas('files', fn ($q) => $q->whereNotNull('extracted_text')->where('extracted_text', '!=', ''))
            ->with('files')
            ->limit((int) $this->option('limit'));

        $fiches = $query->get();

        if ($fiches->isEmpty()) {
            $this->info('No fiches to process.');

            return self::SUCCESS;
        }

        $this->info("Processing {$fiches->count()} fiches...");

        $processed = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar($fiches->count());
        $bar->start();

        foreach ($fiches as $fiche) {
            $texts = $fiche->files
                ->filter(fn ($f) => ! empty($f->extracted_text))
                ->pluck('extracted_text')
                ->toArray();

            if (empty($texts)) {
                $skipped++;
                $bar->advance();

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line(" [dry-run] Would process: {$fiche->title}");
                $bar->advance();

                continue;
            }

            $analysis = $aiService->analyzeFiles($texts, $fiche->title, strip_tags($fiche->description ?? ''));

            if (! $analysis) {
                $this->warn(" Skipped (AI unavailable): {$fiche->title}");
                $skipped++;
                $bar->advance();

                continue;
            }

            $fiche->updateQuietly([
                'ai_suggestions' => [
                    'title' => $analysis['suggested_title'] ?? null,
                    'description' => self::markdownToHtml($analysis['description'] ?? null),
                    'preparation' => self::markdownToHtml($analysis['preparation'] ?? null),
                    'inventory' => self::markdownToHtml($analysis['inventory'] ?? null),
                    'process' => self::markdownToHtml($analysis['process'] ?? null),
                    'duration_estimate' => $analysis['duration_estimate'] ?? null,
                    'group_size_estimate' => $analysis['group_size_estimate'] ?? null,
                    'applied' => [],
                ],
            ]);

            $processed++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Processed {$processed} fiches. Skipped {$skipped}.");

        return self::SUCCESS;
    }

    private static function markdownToHtml(?string $markdown): ?string
    {
        if ($markdown === null || trim($markdown) === '') {
            return $markdown;
        }

        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
