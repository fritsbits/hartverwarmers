# Merge Stub Import Users Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Merge 106 duplicate stub import users (`@import.hartverwarmers.be`) into their matching real registered users, moving all owned data and soft-deleting the stubs.

**Architecture:** A single Artisan command `app:merge-stub-users` that matches stub users to real users by normalized name, reassigns all owned records (fiches, comments, likes, file_uploads, user_interactions, initiatives) in a per-pair transaction, copies missing profile fields, and soft-deletes the stub. Likes with unique constraint conflicts are merged by summing counts.

**Tech Stack:** Laravel 12, PHPUnit

---

## Scope

- **106 stub/real pairs** matched by `LOWER(first_name + last_name)`
- **6 tables** to reassign: `fiches`, `comments`, `likes`, `file_uploads`, `user_interactions`, `initiatives`
- **Likes dedup**: unique constraint on `[user_id, likeable_type, likeable_id, type]` — if both stub and real liked the same fiche, sum `count` into real's like and delete stub's
- **Profile fields**: copy `organisation`, `function_title`, `bio` from stub → real if real's field is null
- **Avatar**: keep real user's avatar (already imported there)
- **Dry-run mode**: `--dry-run` flag to preview without making changes

## File Structure

### New files
- `app/Console/Commands/MergeStubUsers.php`
- `tests/Feature/MergeStubUsersTest.php`

---

## Task 1: Write the tests

**Files:**
- Create: `tests/Feature/MergeStubUsersTest.php`

- [ ] **Step 1: Create the test file**

```php
<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Like;
use App\Models\User;
use App\Models\UserInteraction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeStubUsersTest extends TestCase
{
    use RefreshDatabase;

    private function createStubAndRealUser(string $firstName = 'Jarne', string $lastName = 'Hennebel'): array
    {
        $stub = User::factory()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower("{$firstName}-{$lastName}@import.hartverwarmers.be"),
        ]);

        $real = User::factory()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => 'jarne.hennebel@kei.be',
        ]);

        return [$stub, $real];
    }

    public function test_moves_fiches_from_stub_to_real_user(): void
    {
        [$stub, $real] = $this->createStubAndRealUser();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $stub->id, 'initiative_id' => $initiative->id]);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertEquals($real->id, $fiche->fresh()->user_id);
    }

    public function test_moves_comments_from_stub_to_real_user(): void
    {
        [$stub, $real] = $this->createStubAndRealUser();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
        $comment = Comment::factory()->create(['user_id' => $stub->id, 'commentable_type' => Fiche::class, 'commentable_id' => $fiche->id]);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertEquals($real->id, $comment->fresh()->user_id);
    }

    public function test_moves_likes_from_stub_to_real_user(): void
    {
        [$stub, $real] = $this->createStubAndRealUser();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
        $like = Like::create(['user_id' => $stub->id, 'likeable_type' => Fiche::class, 'likeable_id' => $fiche->id, 'type' => 'kudos', 'count' => 3]);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertEquals($real->id, $like->fresh()->user_id);
    }

    public function test_merges_duplicate_likes_by_summing_count(): void
    {
        [$stub, $real] = $this->createStubAndRealUser();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Like::create(['user_id' => $stub->id, 'likeable_type' => Fiche::class, 'likeable_id' => $fiche->id, 'type' => 'kudos', 'count' => 5]);
        $realLike = Like::create(['user_id' => $real->id, 'likeable_type' => Fiche::class, 'likeable_id' => $fiche->id, 'type' => 'kudos', 'count' => 3]);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertEquals(8, $realLike->fresh()->count);
        $this->assertDatabaseMissing('likes', ['user_id' => $stub->id]);
    }

    public function test_moves_user_interactions_from_stub_to_real(): void
    {
        [$stub, $real] = $this->createStubAndRealUser();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
        UserInteraction::create(['user_id' => $stub->id, 'interactable_type' => Fiche::class, 'interactable_id' => $fiche->id, 'type' => 'view']);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertDatabaseHas('user_interactions', ['user_id' => $real->id, 'type' => 'view']);
        $this->assertDatabaseMissing('user_interactions', ['user_id' => $stub->id]);
    }

    public function test_moves_initiative_created_by_from_stub_to_real(): void
    {
        [$stub, $real] = $this->createStubAndRealUser();
        $initiative = Initiative::factory()->published()->create(['created_by' => $stub->id]);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertEquals($real->id, $initiative->fresh()->created_by);
    }

    public function test_copies_profile_fields_from_stub_when_real_is_empty(): void
    {
        $stub = User::factory()->create([
            'first_name' => 'An',
            'last_name' => 'Test',
            'email' => 'an-test@import.hartverwarmers.be',
            'organisation' => 'WZC Zonneveld',
            'function_title' => 'Animator',
            'bio' => 'Een bio.',
        ]);

        $real = User::factory()->create([
            'first_name' => 'An',
            'last_name' => 'Test',
            'email' => 'an@test.be',
            'organisation' => null,
            'function_title' => null,
            'bio' => null,
        ]);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $real->refresh();
        $this->assertEquals('WZC Zonneveld', $real->organisation);
        $this->assertEquals('Animator', $real->function_title);
        $this->assertEquals('Een bio.', $real->bio);
    }

    public function test_does_not_overwrite_existing_profile_fields(): void
    {
        $stub = User::factory()->create([
            'first_name' => 'An',
            'last_name' => 'Test',
            'email' => 'an-test@import.hartverwarmers.be',
            'organisation' => 'Old Org',
        ]);

        $real = User::factory()->create([
            'first_name' => 'An',
            'last_name' => 'Test',
            'email' => 'an@test.be',
            'organisation' => 'Current Org',
        ]);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertEquals('Current Org', $real->fresh()->organisation);
    }

    public function test_soft_deletes_stub_user(): void
    {
        [$stub, $real] = $this->createStubAndRealUser();

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertSoftDeleted('users', ['id' => $stub->id]);
        $this->assertNull(User::find($real->id)->deleted_at);
    }

    public function test_dry_run_does_not_change_data(): void
    {
        [$stub, $real] = $this->createStubAndRealUser();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $stub->id, 'initiative_id' => $initiative->id]);

        $this->artisan('app:merge-stub-users', ['--dry-run' => true])->assertSuccessful();

        $this->assertEquals($stub->id, $fiche->fresh()->user_id);
        $this->assertNull(User::find($stub->id)->deleted_at);
    }

    public function test_skips_stub_without_matching_real_user(): void
    {
        User::factory()->create([
            'first_name' => 'Orphan',
            'last_name' => 'Stub',
            'email' => 'orphan-stub@import.hartverwarmers.be',
        ]);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'orphan-stub@import.hartverwarmers.be', 'deleted_at' => null]);
    }

    public function test_moves_file_uploads_from_stub_to_real(): void
    {
        [$stub, $real] = $this->createStubAndRealUser();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
        $file = \App\Models\File::factory()->create(['fiche_id' => $fiche->id]);
        \App\Models\FileUpload::create([
            'user_id' => $stub->id,
            'file_id' => $file->id,
            'ip_address' => '127.0.0.1',
            'sha256_hash' => hash('sha256', 'test'),
        ]);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertDatabaseHas('file_uploads', ['user_id' => $real->id]);
        $this->assertDatabaseMissing('file_uploads', ['user_id' => $stub->id]);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter=MergeStubUsersTest
```

Expected: All tests fail with "command not registered" or similar.

---

## Task 2: Write the command

**Files:**
- Create: `app/Console/Commands/MergeStubUsers.php`

- [ ] **Step 3: Create the command**

```php
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
        $skippedNoMatch = 0;

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
        return DB::table('users as stub')
            ->join('users as real', function ($join) {
                $join->on(DB::raw('LOWER(CONCAT(stub.first_name, stub.last_name))'), '=', DB::raw('LOWER(CONCAT(real.first_name, real.last_name))'));
            })
            ->where('stub.email', 'like', '%@import.hartverwarmers.be')
            ->where('real.email', 'not like', '%@import.hartverwarmers.be')
            ->whereNull('stub.deleted_at')
            ->whereNull('real.deleted_at')
            ->select(
                'stub.id as stub_id',
                'real.id as real_id',
                'stub.first_name',
                'stub.last_name',
            )
            ->get();
    }

    private function mergeUser(int $stubId, int $realId): void
    {
        // 1. Move fiches
        DB::table('fiches')->where('user_id', $stubId)->update(['user_id' => $realId]);

        // 2. Move comments
        DB::table('comments')->where('user_id', $stubId)->update(['user_id' => $realId]);

        // 3. Move likes (handle unique constraint conflicts)
        $this->mergeLikes($stubId, $realId);

        // 4. Move file_uploads
        DB::table('file_uploads')->where('user_id', $stubId)->update(['user_id' => $realId]);

        // 5. Move user_interactions
        DB::table('user_interactions')->where('user_id', $stubId)->update(['user_id' => $realId]);

        // 6. Move initiatives created_by
        DB::table('initiatives')->where('created_by', $stubId)->update(['created_by' => $realId]);

        // 7. Copy profile fields if real user's are empty
        $this->copyProfileFields($stubId, $realId);

        // 8. Soft-delete stub
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
                // Conflict: sum counts into real's like, delete stub's
                DB::table('likes')->where('id', $realLike->id)->update([
                    'count' => $realLike->count + $stubLike->count,
                ]);
                DB::table('likes')->where('id', $stubLike->id)->delete();
            } else {
                // No conflict: just reassign
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
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=MergeStubUsersTest
```

Expected: All tests pass.

- [ ] **Step 5: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add app/Console/Commands/MergeStubUsers.php tests/Feature/MergeStubUsersTest.php
git commit -m "feat: add command to merge stub import users into real accounts"
```

---

## Task 3: Execute the merge

- [ ] **Step 1: Dry run**

```bash
php artisan app:merge-stub-users --dry-run
```

Verify the count matches expectations (~106 pairs).

- [ ] **Step 2: Run for real**

```bash
php artisan app:merge-stub-users
```

- [ ] **Step 3: Verify**

```bash
php artisan tinker --execute="echo 'Remaining stubs: ' . App\Models\User::where('email', 'like', '%@import.hartverwarmers.be')->whereNull('deleted_at')->count();"
```

Expected: 0 (or a small number of orphan stubs with no matching real user).
