<?php

namespace App\Console\Commands;

use App\Jobs\AssessFicheQuality;
use App\Models\Fiche;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AssessQuality extends Command
{
    protected $signature = 'fiches:assess-quality {--limit=10 : Number of fiches to assess}';

    protected $description = 'Assess quality of unassessed fiches in batches, newest first';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $remaining = Fiche::query()->published()->whereNull('quality_assessed_at')->count();

        if ($remaining === 0) {
            $this->info('All published fiches have been assessed!');

            return self::SUCCESS;
        }

        $fiches = Fiche::query()
            ->published()
            ->whereNull('quality_assessed_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $count = $fiches->count();
        $this->info("Assessing {$count} fiches ({$remaining} remaining)...");
        $this->newLine();

        foreach ($fiches as $i => $fiche) {
            $number = str_pad(($i + 1).'/'.$count, 6);

            (new AssessFicheQuality($fiche))->handle();
            $fiche->refresh();

            if ($fiche->quality_score !== null) {
                $q = str_pad('Q:'.$fiche->quality_score, 5);
                $p = str_pad('P:'.$fiche->presentation_score, 5);
                $this->line(" {$number}  <info>{$q}</info>  <info>{$p}</info>  ".Str::limit($fiche->title, 25).'  <comment>'.Str::limit($fiche->quality_justification ?? '', 60).'</comment>');
            } else {
                $this->line(" {$number}  <error>MISLUKT</error>  ".Str::limit($fiche->title, 25));
            }

            if ($i < $count - 1) {
                sleep(1);
            }
        }

        $newRemaining = Fiche::query()->published()->whereNull('quality_assessed_at')->count();
        $this->newLine();
        $this->info("Done! {$count} assessed. {$newRemaining} remaining.");

        return self::SUCCESS;
    }
}
