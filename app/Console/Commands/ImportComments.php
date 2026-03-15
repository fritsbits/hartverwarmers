<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportComments extends Command
{
    protected $signature = 'app:import-comments';

    protected $description = 'Import comments from old Soulcenter database';

    public function handle(): int
    {
        // Build migration_id → fiche_id map
        $ficheMap = DB::table('fiches')
            ->whereNotNull('migration_id')
            ->pluck('id', 'migration_id');

        // Build old_email → new_user_id map
        $oldUsers = DB::connection('soulcenter')
            ->table('users')
            ->whereNull('deleted_at')
            ->pluck('email', 'id');

        $newUsersByEmail = DB::table('users')
            ->pluck('id', 'email')
            ->mapWithKeys(fn ($id, $email) => [strtolower(trim($email)) => $id]);

        // Fallback user
        $importUser = DB::table('users')->where('email', 'import@hartverwarmers.be')->first();
        if (! $importUser) {
            $this->error('Import user (import@hartverwarmers.be) not found. Run app:import-users first.');

            return self::FAILURE;
        }

        // Load old comments
        $oldComments = DB::connection('soulcenter')
            ->table('comments')
            ->where('commentable_type', 'App\\Models\\Activity')
            ->whereNull('deleted_at')
            ->select('id', 'commentable_id', 'user_id', 'comment', 'created_at')
            ->get();

        $this->info("Found {$oldComments->count()} comments to import");

        $imported = 0;
        $skippedNoFiche = 0;
        $skippedEmpty = 0;
        $orphanedUser = 0;

        DB::transaction(function () use ($oldComments, $ficheMap, $oldUsers, $newUsersByEmail, $importUser, &$imported, &$skippedNoFiche, &$skippedEmpty, &$orphanedUser) {
            foreach ($oldComments as $comment) {
                // Skip empty body
                if (empty($comment->comment) || trim($comment->comment) === '') {
                    $skippedEmpty++;

                    continue;
                }

                // Map activity → fiche
                $ficheId = $ficheMap[$comment->commentable_id] ?? null;
                if (! $ficheId) {
                    $skippedNoFiche++;

                    continue;
                }

                // Map old user → new user
                $oldEmail = $oldUsers[$comment->user_id] ?? null;
                $newUserId = null;
                if ($oldEmail) {
                    $newUserId = $newUsersByEmail[strtolower(trim($oldEmail))] ?? null;
                }
                if (! $newUserId) {
                    $newUserId = $importUser->id;
                    $orphanedUser++;
                }

                // Idempotency: skip if same comment already exists
                $exists = DB::table('comments')
                    ->where('commentable_type', 'App\\Models\\Fiche')
                    ->where('commentable_id', $ficheId)
                    ->where('user_id', $newUserId)
                    ->where('body', $comment->comment)
                    ->where('created_at', $comment->created_at)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('comments')->insert([
                    'user_id' => $newUserId,
                    'commentable_type' => 'App\\Models\\Fiche',
                    'commentable_id' => $ficheId,
                    'body' => $comment->comment,
                    'parent_id' => null,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->created_at,
                ]);

                $imported++;
            }
        });

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Imported', $imported],
            ['Skipped (no matching fiche)', $skippedNoFiche],
            ['Skipped (empty body)', $skippedEmpty],
            ['Attributed to import user', $orphanedUser],
        ]);

        return self::SUCCESS;
    }
}
