<?php

namespace App\Console\Commands;

use App\Ai\Agents\IconSelector;
use App\Jobs\AssignFicheIcon;
use App\Models\Fiche;
use Illuminate\Console\Command;

class AssignFicheIcons extends Command
{
    protected $signature = 'fiches:assign-icons
        {--force : Re-assign icons even for fiches that already have one}
        {--batch : Process in batches via a single AI call per batch (recommended for backfill)}';

    protected $description = 'Assign Lucide icons to fiches using AI';

    public function handle(): int
    {
        $query = Fiche::query();

        if (! $this->option('force')) {
            $query->whereNull('icon');
        }

        $fiches = $query->get();

        if ($fiches->isEmpty()) {
            $this->info('No fiches to process.');

            return self::SUCCESS;
        }

        if ($this->option('batch')) {
            return $this->processBatch($fiches);
        }

        $this->info("Dispatching {$fiches->count()} jobs...");

        foreach ($fiches as $fiche) {
            AssignFicheIcon::dispatch($fiche);
        }

        $this->info('Done! Icons will be assigned as jobs are processed.');

        return self::SUCCESS;
    }

    /**
     * Process fiches in batches, sending ~40 titles per AI call.
     */
    private function processBatch($fiches): int
    {
        $allowlist = config('fiche-icons.allowlist');
        $icons = implode(', ', $allowlist);
        $chunks = $fiches->chunk(40);
        $assigned = 0;
        $failed = 0;

        $this->info("Processing {$fiches->count()} fiches in {$chunks->count()} batches...");
        $bar = $this->output->createProgressBar($chunks->count());
        $bar->start();

        foreach ($chunks as $chunk) {
            $titleList = $chunk->map(fn (Fiche $f) => "- {$f->id}: {$f->title}")->implode("\n");

            $prompt = <<<PROMPT
            Here are activity titles from a Dutch elderly care platform. For each one, pick the best icon.

            {$titleList}

            Respond with one line per activity in this exact format (no other text):
            ID: icon-name

            Available icons: {$icons}
            PROMPT;

            try {
                $response = (new IconSelector)->prompt($prompt);
                $lines = explode("\n", trim((string) $response));

                foreach ($lines as $line) {
                    if (preg_match('/^(\d+):\s*(.+)$/', trim($line), $matches)) {
                        $ficheId = (int) $matches[1];
                        $icon = trim($matches[2]);

                        if (! in_array($icon, $allowlist)) {
                            $icon = 'file-text';
                        }

                        $fiche = $chunk->firstWhere('id', $ficheId);
                        if ($fiche) {
                            $fiche->updateQuietly(['icon' => $icon]);
                            $assigned++;
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->newLine();
                $this->warn("Batch failed: {$e->getMessage()}");
                $failed += $chunk->count();
            }

            $bar->advance();
            usleep(1_000_000); // 1s delay between batches
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done! Assigned {$assigned} icons. {$failed} failed.");

        return self::SUCCESS;
    }
}
