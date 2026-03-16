# Import Author Avatars Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Import 56 author avatar images from the old Soulcenter backup and link them to the corresponding users in the new system.

**Architecture:** A single Artisan command `app:import-avatars` that copies avatar files from the backup media folder to the public storage, generates thumbnails, and updates the `users.avatar_path` column. Matches authors to new users via email or stub user name.

**Tech Stack:** Laravel 12, Imagick (for thumbnail generation)

---

## Scope

- **56 authors** have avatar images in old DB media table (collection='avatar', model_type='Profile')
- **26** match new users by email
- **29** match stub import users by name
- **1** unmatched (Leen Deconinck — can be manually resolved)
- Avatar files exist in backup at `/Users/frederikvincx/Downloads/hartverwarmers/activiteiten/tmp_backup/media/{media_id}/{filename}`
- New system stores avatars at `avatars/{user_id}.{ext}` with thumbnails at `avatars/{user_id}-thumb.{ext}`

---

## File Structure

### New files
- `app/Console/Commands/ImportAvatars.php`
- `tests/Feature/ImportAvatarsTest.php`

---

## Task 1: ImportAvatars command

**Files:**
- Create: `app/Console/Commands/ImportAvatars.php`
- Create: `tests/Feature/ImportAvatarsTest.php`

- [ ] **Step 1: Write the test**

Create `tests/Feature/ImportAvatarsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportAvatarsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS authors (
            id INT UNSIGNED PRIMARY KEY, name VARCHAR(255), email VARCHAR(255),
            user_id INT UNSIGNED, profile_id BIGINT UNSIGNED, company VARCHAR(255), image VARCHAR(500)
        )');
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS media (
            id BIGINT UNSIGNED PRIMARY KEY, model_type VARCHAR(255), model_id BIGINT UNSIGNED,
            collection_name VARCHAR(255), name VARCHAR(255), file_name VARCHAR(255),
            mime_type VARCHAR(255), disk VARCHAR(255), size BIGINT UNSIGNED,
            order_column INT UNSIGNED, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL
        )');
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS users (
            id INT UNSIGNED PRIMARY KEY, email VARCHAR(255), name VARCHAR(255),
            password VARCHAR(100), created_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL
        )');
    }

    public function test_imports_avatar_for_user_matched_by_email(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['email' => 'author@example.com', 'avatar_path' => null]);

        // Old author with user_id → old user email
        DB::connection('soulcenter')->table('users')->insert([
            'id' => 100, 'email' => 'author@example.com', 'name' => 'Test', 'password' => 'x', 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('authors')->insert([
            'id' => 1, 'name' => 'Test Author', 'email' => null, 'user_id' => 100, 'profile_id' => 5000,
        ]);
        DB::connection('soulcenter')->table('media')->insert([
            'id' => 2375, 'model_type' => 'App\\Models\\Profile', 'model_id' => 5000,
            'collection_name' => 'avatar', 'name' => 'avatar', 'file_name' => 'test.jpeg',
            'mime_type' => 'image/jpeg', 'disk' => 'media', 'size' => 15000,
            'created_at' => now(),
        ]);

        // Create source file in a temp location
        $sourcePath = sys_get_temp_dir() . '/test-avatar-media/2375';
        @mkdir($sourcePath, 0755, true);
        // Create a minimal valid JPEG
        $img = imagecreatetruecolor(100, 100);
        imagejpeg($img, $sourcePath . '/test.jpeg');
        imagedestroy($img);

        $this->artisan('app:import-avatars', [
            'source' => sys_get_temp_dir() . '/test-avatar-media',
        ])->assertSuccessful();

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        $this->assertTrue(Storage::disk('public')->exists($user->avatar_path));

        // Cleanup
        @unlink($sourcePath . '/test.jpeg');
        @rmdir($sourcePath);
    }

    public function test_skips_user_who_already_has_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'avatar_path' => 'avatars/existing.jpg',
        ]);

        DB::connection('soulcenter')->table('users')->insert([
            'id' => 200, 'email' => 'existing@example.com', 'name' => 'Test', 'password' => 'x', 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('authors')->insert([
            'id' => 2, 'name' => 'Existing Author', 'email' => null, 'user_id' => 200, 'profile_id' => 6000,
        ]);
        DB::connection('soulcenter')->table('media')->insert([
            'id' => 9999, 'model_type' => 'App\\Models\\Profile', 'model_id' => 6000,
            'collection_name' => 'avatar', 'name' => 'avatar', 'file_name' => 'old.jpeg',
            'mime_type' => 'image/jpeg', 'disk' => 'media', 'size' => 10000,
            'created_at' => now(),
        ]);

        $this->artisan('app:import-avatars', [
            'source' => sys_get_temp_dir() . '/nonexistent-media',
        ])->assertSuccessful();

        $user->refresh();
        $this->assertEquals('avatars/existing.jpg', $user->avatar_path);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter=ImportAvatarsTest
```

- [ ] **Step 3: Write the command**

Create `app/Console/Commands/ImportAvatars.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Imagick;

class ImportAvatars extends Command
{
    protected $signature = 'app:import-avatars {source : Path to the media backup folder}';

    protected $description = 'Import author avatars from old Soulcenter backup media';

    public function handle(): int
    {
        $sourcePath = rtrim($this->argument('source'), '/');
        if (! is_dir($sourcePath)) {
            $this->error("Source directory not found: {$sourcePath}");
            return self::FAILURE;
        }

        // Load author → avatar media mapping from old DB
        $authorAvatars = DB::connection('soulcenter')
            ->table('authors as a')
            ->join('media as m', function ($join) {
                $join->on('m.model_id', '=', 'a.profile_id')
                    ->where('m.model_type', 'App\\Models\\Profile')
                    ->where('m.collection_name', 'avatar');
            })
            ->leftJoin('users as u', 'a.user_id', '=', 'u.id')
            ->select('a.id as author_id', 'a.name', 'a.email as author_email',
                'u.email as user_email', 'm.id as media_id', 'm.file_name', 'm.mime_type')
            ->get();

        $this->info("Found {$authorAvatars->count()} author avatars in old DB");

        // Build lookup maps for new users
        $newUsersByEmail = DB::table('users')
            ->whereNotNull('email')
            ->get()
            ->keyBy(fn ($u) => strtolower(trim($u->email)));

        $stubUsersByName = DB::table('users')
            ->where('email', 'like', '%@import.hartverwarmers.be')
            ->get()
            ->keyBy(fn ($u) => strtolower(trim($u->first_name . ' ' . $u->last_name)));

        $imported = 0;
        $skippedHasAvatar = 0;
        $skippedNoUser = 0;
        $skippedNoFile = 0;

        foreach ($authorAvatars as $avatar) {
            // Find matching new user
            $newUser = null;

            // Try by email first
            $email = null;
            if ($avatar->user_email) {
                $email = strtolower(trim($avatar->user_email));
            } elseif ($avatar->author_email) {
                $email = strtolower(trim(explode(',', $avatar->author_email)[0]));
            }

            if ($email) {
                $newUser = $newUsersByEmail[$email] ?? null;
            }

            // Fall back to stub user by name
            if (! $newUser) {
                $newUser = $stubUsersByName[strtolower(trim($avatar->name))] ?? null;
            }

            if (! $newUser) {
                $skippedNoUser++;
                $this->line("  No user match: {$avatar->name}");
                continue;
            }

            // Skip if user already has an avatar
            if ($newUser->avatar_path) {
                $skippedHasAvatar++;
                continue;
            }

            // Check if source file exists
            $sourceFile = "{$sourcePath}/{$avatar->media_id}/{$avatar->file_name}";
            if (! file_exists($sourceFile)) {
                $skippedNoFile++;
                $this->warn("  Missing file: {$sourceFile}");
                continue;
            }

            // Determine extension
            $ext = strtolower(pathinfo($avatar->file_name, PATHINFO_EXTENSION));
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $ext = 'jpg';
            }

            // Copy and resize avatar
            $avatarRelativePath = "avatars/{$newUser->id}.{$ext}";
            $thumbRelativePath = "avatars/{$newUser->id}-thumb.{$ext}";

            try {
                // Resize to max 400x400 for avatar
                $img = new Imagick($sourceFile);
                $img->setImageFormat($ext === 'jpg' ? 'jpeg' : $ext);
                $img->thumbnailImage(400, 400, true);
                Storage::disk('public')->put($avatarRelativePath, $img->getImageBlob());

                // Generate thumbnail (80x80)
                $img->thumbnailImage(80, 80, true);
                Storage::disk('public')->put($thumbRelativePath, $img->getImageBlob());
                $img->destroy();

                // Update user
                DB::table('users')
                    ->where('id', $newUser->id)
                    ->update(['avatar_path' => $avatarRelativePath]);

                $imported++;
                $this->line("  Imported: {$avatar->name} → {$avatarRelativePath}");
            } catch (\Throwable $e) {
                $this->warn("  Error processing {$avatar->name}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Imported', $imported],
            ['Skipped (already has avatar)', $skippedHasAvatar],
            ['Skipped (no matching user)', $skippedNoUser],
            ['Skipped (file not found)', $skippedNoFile],
        ]);

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=ImportAvatarsTest
```

Expected: Both tests pass.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Console/Commands/ImportAvatars.php tests/Feature/ImportAvatarsTest.php
git commit -m "feat: add command to import author avatars from old backup"
```

---

## Task 2: Execute avatar import

- [ ] **Step 1: Run the command**

```bash
php artisan app:import-avatars /Users/frederikvincx/Downloads/hartverwarmers/activiteiten/tmp_backup/media
```

Expected: ~55 avatars imported.

- [ ] **Step 2: Verify**

```bash
php artisan tinker --execute="echo App\Models\User::whereNotNull('avatar_path')->count();"
```
