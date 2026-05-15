<?php

namespace App\Console\Commands;

use App\Models\Okr\Initiative;
use App\Models\Okr\Objective;
use App\Services\Okr\InitiativeImpact;
use App\Services\Okr\MetricRegistry;
use App\Services\Okr\ObjectiveStatBuilder;
use Illuminate\Console\Command;
use Throwable;

class WarmOkrMetricsCommand extends Command
{
    protected $signature = 'okr:warm-metrics';

    protected $description = 'Pre-compute and cache all OKR dashboard metrics so admin page loads are warm';

    private const array RANGES = ['week', 'month', 'quarter', 'alltime'];

    public function handle(
        MetricRegistry $registry,
        ObjectiveStatBuilder $statBuilder,
        InitiativeImpact $impact,
    ): int {
        $failures = 0;

        $objectives = Objective::with('keyResults')->orderBy('position')->get();

        foreach (self::RANGES as $range) {
            try {
                $statBuilder->build($objectives, $range);
            } catch (Throwable $e) {
                $failures++;
                report($e);
                $this->components->error("stat builder ({$range}): {$e->getMessage()}");
            }
        }

        $startedInitiatives = Initiative::query()
            ->whereNotNull('started_at')
            ->with(['objective.keyResults', 'baselines'])
            ->get();

        foreach ($startedInitiatives as $initiative) {
            try {
                $impact->forInitiative($initiative);
            } catch (Throwable $e) {
                $failures++;
                report($e);
                $this->components->error("initiative {$initiative->slug}: {$e->getMessage()}");
            }
        }

        foreach (array_keys(config('okr-metrics', [])) as $key) {
            foreach (self::RANGES as $range) {
                try {
                    $registry->compute($key, $range);
                } catch (Throwable $e) {
                    $failures++;
                    report($e);
                    $this->components->error("metric {$key} ({$range}): {$e->getMessage()}");
                }
            }
        }

        if ($failures > 0) {
            $this->components->warn("OKR metrics warmed with {$failures} failure(s).");

            return self::FAILURE;
        }

        $this->components->info('OKR metrics warmed.');

        return self::SUCCESS;
    }
}
