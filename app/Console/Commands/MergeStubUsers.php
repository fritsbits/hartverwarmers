<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MergeStubUsers extends Command
{
    protected $signature = 'app:merge-stub-users {--dry-run : Preview changes without modifying data}';

    protected $description = 'Merge stub import users (@import.hartverwarmers.be) into their matching real users';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be made.');
        }

        $pairs = $this->findPairs();
        $this->info("Found {$pairs->count()} stub/real user pairs.");

        $merged = 0;

        foreach ($pairs as $pair) {
            if ($dryRun) {
                $this->line("  Would merge: #{$pair->stub_id} → #{$pair->real_id} ({$pair->first_name} {$pair->last_name})");
                $merged++;

                continue;
            }

            try {
                DB::transaction(function () use ($pair) {
                    $this->mergeUser($pair->stub_id, $pair->real_id);
                });
                $merged++;
                $this->line("  Merged: #{$pair->stub_id} → #{$pair->real_id} ({$pair->first_name} {$pair->last_name})");
            } catch (\Throwable $e) {
                $this->error("  Failed: {$pair->first_name} {$pair->last_name} — {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Merged', $merged],
            ['Total pairs found', $pairs->count()],
        ]);

        return self::SUCCESS;
    }

    private function findPairs()
    {
        // Get all candidates (a stub may match multiple real users)
        $candidates = DB::table('users as s')
            ->join('users as r', function ($join) {
                $join->on(DB::raw('LOWER(CONCAT(s.first_name, s.last_name))'), '=', DB::raw('LOWER(CONCAT(r.first_name, r.last_name))'));
            })
            ->where('s.email', 'like', '%@import.hartverwarmers.be')
            ->where('r.email', 'not like', '%@import.hartverwarmers.be')
            ->whereNull('s.deleted_at')
            ->whereNull('r.deleted_at')
            ->select(
                's.id as stub_id',
                'r.id as real_id',
                's.first_name',
                's.last_name',
                DB::raw('(SELECT COUNT(*) FROM fiches WHERE user_id = r.id) as real_fiches'),
                'r.created_at as real_created_at',
            )
            ->get();

        // For each stub, pick the real user with most fiches (tie-break: earliest created)
        return $candidates->groupBy('stub_id')->map(function ($group) {
            return $group->sortByDesc('real_fiches')->sortBy('real_created_at')->first();
        })->values();
    }

    private function mergeUser(int $stubId, int $realId): void
    {
        DB::table('fiches')->where('user_id', $stubId)->update(['user_id' => $realId]);

        DB::table('comments')->where('user_id', $stubId)->update(['user_id' => $realId]);

        $this->mergeLikes($stubId, $realId);

        DB::table('file_uploads')->where('user_id', $stubId)->update(['user_id' => $realId]);

        DB::table('user_interactions')->where('user_id', $stubId)->update(['user_id' => $realId]);

        DB::table('initiatives')->where('created_by', $stubId)->update(['created_by' => $realId]);

        $this->copyProfileFields($stubId, $realId);

        DB::table('users')->where('id', $stubId)->update(['deleted_at' => now()]);
    }

    private function mergeLikes(int $stubId, int $realId): void
    {
        $stubLikes = DB::table('likes')->where('user_id', $stubId)->get();

        foreach ($stubLikes as $stubLike) {
            $realLike = DB::table('likes')
                ->where('user_id', $realId)
                ->where('likeable_type', $stubLike->likeable_type)
                ->where('likeable_id', $stubLike->likeable_id)
                ->where('type', $stubLike->type)
                ->first();

            if ($realLike) {
                DB::table('likes')->where('id', $realLike->id)->update([
                    'count' => $realLike->count + $stubLike->count,
                ]);
                DB::table('likes')->where('id', $stubLike->id)->delete();
            } else {
                DB::table('likes')->where('id', $stubLike->id)->update(['user_id' => $realId]);
            }
        }
    }

    private function copyProfileFields(int $stubId, int $realId): void
    {
        $stub = DB::table('users')->where('id', $stubId)->first();
        $real = DB::table('users')->where('id', $realId)->first();

        $updates = [];

        foreach (['organisation', 'function_title', 'bio'] as $field) {
            if (empty($real->$field) && ! empty($stub->$field)) {
                $updates[$field] = $stub->$field;
            }
        }

        if ($updates) {
            DB::table('users')->where('id', $realId)->update($updates);
        }
    }
}
