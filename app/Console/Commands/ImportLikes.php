<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLikes extends Command
{
    protected $signature = 'app:import-likes';

    protected $description = 'Import likes from old Soulcenter database and recalculate kudos counts';

    public function handle(): int
    {
        // Build maps
        $ficheMap = DB::table('fiches')
            ->whereNotNull('migration_id')
            ->pluck('id', 'migration_id');

        $oldUsers = DB::connection('soulcenter')
            ->table('users')
            ->whereNull('deleted_at')
            ->pluck('email', 'id');

        $newUsersByEmail = DB::table('users')
            ->pluck('id', 'email')
            ->mapWithKeys(fn ($id, $email) => [strtolower(trim($email)) => $id]);

        // Load old likes on activities
        $oldLikes = DB::connection('soulcenter')
            ->table('likes')
            ->where('likeable_type', 'App\\Models\\Activity')
            ->whereNotNull('user_id')
            ->select('id', 'user_id', 'likeable_id', 'created_at')
            ->get();

        $this->info("Found {$oldLikes->count()} likes to import");

        $imported = 0;
        $skippedNoFiche = 0;
        $skippedNoUser = 0;
        $skippedDuplicate = 0;

        // Track seen combinations to deduplicate
        $seen = [];

        DB::transaction(function () use ($oldLikes, $ficheMap, $oldUsers, $newUsersByEmail, &$imported, &$skippedNoFiche, &$skippedNoUser, &$skippedDuplicate, &$seen) {
            foreach ($oldLikes as $like) {
                $ficheId = $ficheMap[$like->likeable_id] ?? null;
                if (! $ficheId) {
                    $skippedNoFiche++;

                    continue;
                }

                $oldEmail = $oldUsers[$like->user_id] ?? null;
                $newUserId = null;
                if ($oldEmail) {
                    $newUserId = $newUsersByEmail[strtolower(trim($oldEmail))] ?? null;
                }
                if (! $newUserId) {
                    $skippedNoUser++;

                    continue;
                }

                // Deduplicate: same user + same fiche
                $key = "{$newUserId}:{$ficheId}";
                if (isset($seen[$key])) {
                    $skippedDuplicate++;

                    continue;
                }
                $seen[$key] = true;

                // Check DB for existing (from previous run or manual entry)
                $exists = DB::table('likes')
                    ->where('user_id', $newUserId)
                    ->where('likeable_type', 'App\\Models\\Fiche')
                    ->where('likeable_id', $ficheId)
                    ->where('type', 'kudos')
                    ->exists();

                if ($exists) {
                    $skippedDuplicate++;

                    continue;
                }

                DB::table('likes')->insert([
                    'user_id' => $newUserId,
                    'session_id' => null,
                    'likeable_type' => 'App\\Models\\Fiche',
                    'likeable_id' => $ficheId,
                    'type' => 'kudos',
                    'count' => 1,
                    'created_at' => $like->created_at,
                    'updated_at' => $like->created_at,
                ]);

                $imported++;
            }
        });

        // Recalculate kudos_count on all fiches
        $this->info('Recalculating kudos_count on all fiches...');
        DB::update(
            'UPDATE fiches SET kudos_count = (
                SELECT COALESCE(SUM(`count`), 0) FROM likes
                WHERE likeable_type = ?
                AND likeable_id = fiches.id
                AND type = ?
            )',
            ['App\Models\Fiche', 'kudos']
        );

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Imported', $imported],
            ['Skipped (no matching fiche)', $skippedNoFiche],
            ['Skipped (no matching user)', $skippedNoUser],
            ['Skipped (duplicate)', $skippedDuplicate],
        ]);

        return self::SUCCESS;
    }
}
