<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MapMigrationIds extends Command
{
    protected $signature = 'app:map-migration-ids';

    protected $description = 'Map old activity IDs to fiches via title matching';

    /**
     * Manual near-matches for titles that differ slightly.
     * Format: old_activity_id => new_fiche_title (lowercase).
     */
    private const NEAR_MATCHES = [
        20377 => 'enveloppe spel',       // Old: "Enveloppenspel"
        20381 => 'quiz gezonde voeding',  // Old: "quiz  gezonde voeding"
    ];

    public function handle(): int
    {
        $this->info('Loading old activities from soulcenter_backup...');

        $oldActivities = DB::connection('soulcenter')
            ->table('activities')
            ->where('published', 1)
            ->whereNull('deleted_at')
            ->select('id', 'title', 'created_at')
            ->get();

        $this->info("Found {$oldActivities->count()} published activities");

        $fiches = DB::table('fiches')
            ->select('id', 'title', 'created_at', 'migration_id')
            ->get();

        $this->info("Found {$fiches->count()} fiches");

        // Group by lowercase title
        $fichesByTitle = $fiches->groupBy(fn ($f) => mb_strtolower(trim($f->title)));
        $activitiesByTitle = $oldActivities->groupBy(fn ($a) => mb_strtolower(trim($a->title)));

        $matched = 0;
        $skipped = 0;
        $unmatched = [];

        // Handle near-matches first — reserve those fiche IDs
        $nearMatchMap = []; // activity_id => fiche_id
        foreach (self::NEAR_MATCHES as $activityId => $ficheTitle) {
            $activity = $oldActivities->firstWhere('id', $activityId);
            if (! $activity) {
                continue;
            }

            $candidates = $fichesByTitle->get($ficheTitle);
            if ($candidates && $candidates->isNotEmpty()) {
                $fiche = $candidates->first();
                $nearMatchMap[$activityId] = $fiche->id;

                // Remove from the groups to avoid double-matching
                $oldTitle = mb_strtolower(trim($activity->title));
                if ($activitiesByTitle->has($oldTitle)) {
                    $activitiesByTitle[$oldTitle] = $activitiesByTitle[$oldTitle]->reject(fn ($a) => $a->id === $activityId);
                    if ($activitiesByTitle[$oldTitle]->isEmpty()) {
                        $activitiesByTitle->forget($oldTitle);
                    }
                }
                $fichesByTitle[$ficheTitle] = $candidates->reject(fn ($f) => $f->id === $fiche->id);
                if ($fichesByTitle[$ficheTitle]->isEmpty()) {
                    $fichesByTitle->forget($ficheTitle);
                }
            }
        }

        // Apply near-matches
        foreach ($nearMatchMap as $activityId => $ficheId) {
            $current = DB::table('fiches')->where('id', $ficheId)->value('migration_id');
            if ($current !== null) {
                $skipped++;

                continue;
            }
            DB::table('fiches')->where('id', $ficheId)->update(['migration_id' => $activityId]);
            $matched++;
        }

        // Match remaining by title
        foreach ($activitiesByTitle as $title => $activities) {
            $candidates = $fichesByTitle->get($title);

            if (! $candidates || $candidates->isEmpty()) {
                foreach ($activities as $a) {
                    $unmatched[] = "Activity {$a->id}: {$a->title}";
                }

                continue;
            }

            // Sort both by created_at for positional matching
            $sortedActivities = $activities->sortBy('created_at')->values();
            $sortedFiches = $candidates->sortBy('created_at')->values();

            foreach ($sortedActivities as $index => $activity) {
                if (! isset($sortedFiches[$index])) {
                    $unmatched[] = "Activity {$activity->id}: {$activity->title} (no fiche at position {$index})";

                    continue;
                }

                $fiche = $sortedFiches[$index];

                // Skip if already mapped
                if ($fiche->migration_id !== null) {
                    $skipped++;

                    continue;
                }

                DB::table('fiches')->where('id', $fiche->id)->update(['migration_id' => $activity->id]);
                $matched++;
            }
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Matched & updated', $matched],
            ['Already mapped (skipped)', $skipped],
            ['Unmatched activities', count($unmatched)],
        ]);

        if (! empty($unmatched)) {
            $this->warn('Unmatched activities:');
            foreach ($unmatched as $msg) {
                $this->line("  - {$msg}");
            }
        }

        return self::SUCCESS;
    }
}
