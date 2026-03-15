# Data Migration Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete the data migration from the old Soulcenter database to the new Hartverwarmers platform — importing users, comments, likes, and files while preserving existing hand-curated icons and initiative mappings.

**Architecture:** A series of idempotent Artisan commands that read from the old `soulcenter_backup` database (via a dedicated DB connection) and write to the local `hartverwarmers_local` database. Each command handles one migration concern, runs in a transaction, and reports results. A `migration_id` column on fiches acts as the glue between old activity IDs and new fiche IDs.

**Tech Stack:** Laravel 12, PHP 8.4, MySQL 8, PHPUnit 11

**Spec:** `docs/superpowers/specs/2026-03-15-data-migration-design.md`

---

## File Structure

### New files
- `database/migrations/2026_03_15_000001_add_migration_id_to_fiches_table.php` — adds `migration_id` column
- `config/database.php` — modified: add `soulcenter` connection
- `app/Console/Commands/MapMigrationIds.php` — maps old activity IDs to fiches
- `app/Console/Commands/ImportUsers.php` — imports users from CSV + old DB
- `app/Console/Commands/RelinkFicheAuthors.php` — re-links fiches to real users
- `app/Console/Commands/ImportComments.php` — imports comments
- `app/Console/Commands/ImportLikes.php` — imports likes + recalculates kudos_count
- `app/Console/Commands/ImportFiles.php` — imports file records
- `app/Console/Commands/CleanupMedia.php` — lists/removes orphaned media folders
- `tests/Feature/MapMigrationIdsTest.php`
- `tests/Feature/ImportUsersTest.php`
- `tests/Feature/RelinkFicheAuthorsTest.php`
- `tests/Feature/ImportCommentsTest.php`
- `tests/Feature/ImportLikesTest.php`
- `tests/Feature/ImportFilesTest.php`

### Modified files
- `app/Models/Fiche.php` — add `migration_id` to `$fillable`

---

## Chunk 1: Foundation (migration_id + DB connection + mapping)

### Task 1: Add `migration_id` column and DB connection

**Files:**
- Create: `database/migrations/2026_03_15_000001_add_migration_id_to_fiches_table.php`
- Modify: `app/Models/Fiche.php:22` ($fillable array)
- Modify: `config/database.php` (add soulcenter connection)

- [ ] **Step 1: Create the migration**

```bash
php artisan make:migration add_migration_id_to_fiches_table --table=fiches --no-interaction
```

Then replace its contents with:

```php
return new class extends Migration {
    public function up(): void
    {
        Schema::table('fiches', function (Blueprint $table) {
            $table->unsignedBigInteger('migration_id')->nullable()->after('icon');
            $table->index('migration_id');
        });
    }

    public function down(): void
    {
        Schema::table('fiches', function (Blueprint $table) {
            $table->dropIndex(['migration_id']);
            $table->dropColumn('migration_id');
        });
    }
};
```

- [ ] **Step 2: Add `migration_id` to Fiche model's $fillable**

In `app/Models/Fiche.php`, add `'migration_id'` to the `$fillable` array after `'icon'`.

- [ ] **Step 3: Add `soulcenter` database connection**

In `config/database.php`, inside the `'connections'` array, add after the `'mysql'` entry:

```php
'soulcenter' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => 'soulcenter_backup',
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => null,
],
```

- [ ] **Step 4: Run the migration**

```bash
php artisan migrate
```

Expected: Migration runs successfully, `migration_id` column added to fiches.

- [ ] **Step 5: Verify DB connection works**

```bash
php artisan tinker --execute="echo DB::connection('soulcenter')->select('SELECT COUNT(*) as cnt FROM activities WHERE published=1 AND deleted_at IS NULL')[0]->cnt;"
```

Expected: `395`

- [ ] **Step 6: Run existing tests to verify nothing broke**

```bash
php artisan test --compact
```

Expected: All existing tests pass.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/*migration_id* app/Models/Fiche.php config/database.php
git commit -m "feat: add migration_id column to fiches and soulcenter DB connection"
```

---

### Task 2: MapMigrationIds command

**Files:**
- Create: `app/Console/Commands/MapMigrationIds.php`
- Create: `tests/Feature/MapMigrationIdsTest.php`

- [ ] **Step 1: Write the test**

Create `tests/Feature/MapMigrationIdsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MapMigrationIdsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create old activities in soulcenter_backup (test uses same DB, simulate with a temp table)
        // We test the command logic by mocking the soulcenter connection to use a temp table
    }

    public function test_maps_fiches_by_exact_title_match(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create();

        $fiche = Fiche::factory()->create([
            'title' => 'Sport- en spelactiviteit',
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
        ]);

        // Create temp table simulating old activities
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS activities (
            id INT UNSIGNED PRIMARY KEY,
            title VARCHAR(255),
            published TINYINT DEFAULT 1,
            deleted_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL
        )');
        DB::connection('soulcenter')->table('activities')->insert([
            'id' => 20337,
            'title' => 'Sport- en spelactiviteit',
            'published' => 1,
            'deleted_at' => null,
            'created_at' => '2026-01-26 12:28:00',
        ]);

        $this->artisan('app:map-migration-ids')
            ->assertSuccessful();

        $this->assertDatabaseHas('fiches', [
            'id' => $fiche->id,
            'migration_id' => 20337,
        ]);
    }

    public function test_handles_duplicate_titles_by_created_at_order(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create();

        $fiche1 = Fiche::factory()->create([
            'title' => 'Muziekquiz',
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
            'created_at' => '2024-11-20 15:14:44',
        ]);
        $fiche2 = Fiche::factory()->create([
            'title' => 'Muziekquiz',
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
            'created_at' => '2025-05-06 10:53:47',
        ]);

        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS activities (
            id INT UNSIGNED PRIMARY KEY,
            title VARCHAR(255),
            published TINYINT DEFAULT 1,
            deleted_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL
        )');
        DB::connection('soulcenter')->insert('INSERT INTO activities VALUES (20199, "Muziekquiz", 1, NULL, "2024-11-20 15:14:44")');
        DB::connection('soulcenter')->insert('INSERT INTO activities VALUES (19886, "Muziekquiz", 1, NULL, "2025-05-06 10:53:47")');

        $this->artisan('app:map-migration-ids')
            ->assertSuccessful();

        $this->assertDatabaseHas('fiches', ['id' => $fiche1->id, 'migration_id' => 20199]);
        $this->assertDatabaseHas('fiches', ['id' => $fiche2->id, 'migration_id' => 19886]);
    }

    public function test_handles_near_match_enveloppenspel(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create();

        $fiche = Fiche::factory()->create([
            'title' => 'Enveloppe spel',
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
        ]);

        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS activities (
            id INT UNSIGNED PRIMARY KEY,
            title VARCHAR(255),
            published TINYINT DEFAULT 1,
            deleted_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL
        )');
        DB::connection('soulcenter')->insert('INSERT INTO activities VALUES (20377, "Enveloppenspel", 1, NULL, "2026-03-01 00:00:00")');

        $this->artisan('app:map-migration-ids')
            ->assertSuccessful();

        $this->assertDatabaseHas('fiches', ['id' => $fiche->id, 'migration_id' => 20377]);
    }

    public function test_is_idempotent(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create();

        $fiche = Fiche::factory()->create([
            'title' => 'Test fiche',
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
            'migration_id' => 99999, // Already mapped
        ]);

        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS activities (
            id INT UNSIGNED PRIMARY KEY,
            title VARCHAR(255),
            published TINYINT DEFAULT 1,
            deleted_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL
        )');
        DB::connection('soulcenter')->insert('INSERT INTO activities VALUES (99999, "Test fiche", 1, NULL, "2026-01-01 00:00:00")');

        $this->artisan('app:map-migration-ids')
            ->assertSuccessful();

        // Should still have the same migration_id
        $this->assertDatabaseHas('fiches', ['id' => $fiche->id, 'migration_id' => 99999]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=MapMigrationIds
```

Expected: FAIL — command not found.

- [ ] **Step 3: Create the command**

Create `app/Console/Commands/MapMigrationIds.php`:

```php
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
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=MapMigrationIds
```

Expected: All 4 tests pass.

- [ ] **Step 5: Run the command against real data**

```bash
php artisan app:map-migration-ids
```

Expected: ~394 matched, 0-1 unmatched.

- [ ] **Step 6: Verify mapping**

```bash
php artisan tinker --execute="echo App\Models\Fiche::whereNotNull('migration_id')->count();"
```

Expected: 394 (or close to it).

- [ ] **Step 7: Commit**

```bash
git add app/Console/Commands/MapMigrationIds.php tests/Feature/MapMigrationIdsTest.php
git commit -m "feat: add command to map old activity IDs to fiches"
```

---

## Chunk 2: Import Users

### Task 3: ImportUsers command

**Files:**
- Create: `app/Console/Commands/ImportUsers.php`
- Create: `tests/Feature/ImportUsersTest.php`

- [ ] **Step 1: Write the test**

Create `tests/Feature/ImportUsersTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ImportUsersTest extends TestCase
{
    use RefreshDatabase;

    private string $csvPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Create temp CSV
        $this->csvPath = sys_get_temp_dir() . '/test-contacts.csv';
        $csv = "id,created_at,first_name,last_name,email,unsubscribed\n";
        $csv .= "uuid1,2026-03-11 11:39:56,Lotte,Boussier,lotte@example.com,false\n";
        $csv .= "uuid2,2026-03-11 11:39:56,Goele,Martens,goele@example.com,false\n";
        file_put_contents($this->csvPath, $csv);

        // Create old users table in soulcenter
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS users (
            id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255),
            email VARCHAR(255),
            password VARCHAR(100),
            created_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        )');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->csvPath)) {
            unlink($this->csvPath);
        }
        parent::tearDown();
    }

    public function test_imports_users_from_csv_with_old_password(): void
    {
        $hashedPassword = Hash::make('old-password');
        DB::connection('soulcenter')->table('users')->insert([
            'id' => 100,
            'name' => 'Lotte Boussier',
            'email' => 'lotte@example.com',
            'password' => $hashedPassword,
            'created_at' => '2020-05-01 10:00:00',
        ]);

        $this->artisan('app:import-users', ['csv' => $this->csvPath])
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'first_name' => 'Lotte',
            'last_name' => 'Boussier',
            'email' => 'lotte@example.com',
            'role' => 'member',
        ]);

        // Verify password was copied (not re-hashed)
        $user = User::where('email', 'lotte@example.com')->first();
        $this->assertEquals($hashedPassword, $user->getRawOriginal('password'));
    }

    public function test_skips_existing_emails(): void
    {
        User::factory()->create(['email' => 'lotte@example.com', 'first_name' => 'Existing']);

        $this->artisan('app:import-users', ['csv' => $this->csvPath])
            ->assertSuccessful();

        // Should NOT overwrite existing user
        $this->assertDatabaseHas('users', [
            'email' => 'lotte@example.com',
            'first_name' => 'Existing',
        ]);
    }

    public function test_imports_additional_interactors(): void
    {
        // Create old DB tables
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS comments (
            id BIGINT UNSIGNED PRIMARY KEY,
            commentable_type VARCHAR(255),
            commentable_id BIGINT UNSIGNED,
            user_id BIGINT UNSIGNED,
            comment TEXT,
            created_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        )');
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS likes (
            id BIGINT UNSIGNED PRIMARY KEY,
            user_id INT UNSIGNED,
            likeable_id INT UNSIGNED,
            likeable_type VARCHAR(255),
            created_at TIMESTAMP NULL
        )');

        // Old user who commented but is NOT in CSV
        DB::connection('soulcenter')->table('users')->insert([
            'id' => 200,
            'name' => 'Helena De Wit',
            'email' => 'helena@example.com',
            'password' => Hash::make('secret'),
            'created_at' => '2023-01-15 09:00:00',
        ]);
        DB::connection('soulcenter')->table('comments')->insert([
            'id' => 1,
            'commentable_type' => 'App\\Models\\Activity',
            'commentable_id' => 100,
            'user_id' => 200,
            'comment' => 'Great!',
            'created_at' => '2023-06-01 12:00:00',
        ]);

        $this->artisan('app:import-users', ['csv' => $this->csvPath])
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'helena@example.com',
            'first_name' => 'Helena',
            'last_name' => 'De Wit',
        ]);
    }

    public function test_csv_contact_without_old_account_gets_random_password(): void
    {
        // goele@example.com is in CSV but NOT in old DB
        $this->artisan('app:import-users', ['csv' => $this->csvPath])
            ->assertSuccessful();

        $user = User::where('email', 'goele@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotEmpty($user->getRawOriginal('password'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=ImportUsersTest
```

Expected: FAIL — command not found.

- [ ] **Step 3: Create the command**

Create `app/Console/Commands/ImportUsers.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportUsers extends Command
{
    protected $signature = 'app:import-users {csv : Path to the contacts CSV file}';

    protected $description = 'Import users from contacts CSV and old DB interactors';

    public function handle(): int
    {
        $csvPath = $this->argument('csv');
        if (! file_exists($csvPath)) {
            $this->error("CSV file not found: {$csvPath}");
            return self::FAILURE;
        }

        $existingEmails = DB::table('users')
            ->pluck('email')
            ->map(fn ($e) => strtolower(trim($e)))
            ->flip();

        $oldUsersByEmail = $this->loadOldUsers();

        // Source A: CSV contacts
        $csvContacts = $this->parseCsv($csvPath);
        $this->info("CSV contacts loaded: " . count($csvContacts));

        $imported = 0;
        $skipped = 0;
        $noPassword = 0;

        DB::transaction(function () use ($csvContacts, $oldUsersByEmail, &$existingEmails, &$imported, &$skipped, &$noPassword) {
            foreach ($csvContacts as $contact) {
                $email = strtolower(trim($contact['email']));

                if ($existingEmails->has($email)) {
                    $skipped++;
                    continue;
                }

                $oldUser = $oldUsersByEmail[$email] ?? null;
                $password = $oldUser?->password ?? bcrypt(Str::random(32));
                if (! $oldUser) {
                    $noPassword++;
                }

                DB::table('users')->insert([
                    'first_name' => $contact['first_name'],
                    'last_name' => $contact['last_name'],
                    'email' => $contact['email'],
                    'password' => $password,
                    'role' => 'member',
                    'email_verified_at' => now(),
                    'created_at' => $oldUser?->created_at ?? now(),
                    'updated_at' => now(),
                ]);

                $existingEmails[$email] = true;
                $imported++;
            }
        });

        $this->info("CSV: {$imported} imported, {$skipped} skipped (existing), {$noPassword} without old password");

        // Source B: Additional interactors from old DB
        $interactorImported = $this->importInteractors($oldUsersByEmail, $existingEmails);

        $this->newLine();
        $this->table(['Source', 'Imported', 'Skipped'], [
            ['CSV contacts', $imported, $skipped],
            ['Old DB interactors', $interactorImported['imported'], $interactorImported['skipped']],
        ]);

        $total = $imported + $interactorImported['imported'];
        $this->info("Total users imported: {$total}");

        return self::SUCCESS;
    }

    private function loadOldUsers(): array
    {
        $users = DB::connection('soulcenter')
            ->table('users')
            ->whereNull('deleted_at')
            ->select('id', 'name', 'email', 'password', 'created_at')
            ->get();

        $byEmail = [];
        foreach ($users as $user) {
            $byEmail[strtolower(trim($user->email))] = $user;
        }

        return $byEmail;
    }

    private function parseCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        while ($row = fgetcsv($handle)) {
            $data = array_combine($header, $row);
            $rows[] = $data;
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Import users who interacted (commented/liked) but aren't in the CSV.
     */
    private function importInteractors(array $oldUsersByEmail, $existingEmails): array
    {
        $interactorIds = DB::connection('soulcenter')
            ->table('comments')
            ->where('commentable_type', 'App\\Models\\Activity')
            ->whereNull('deleted_at')
            ->pluck('user_id')
            ->merge(
                DB::connection('soulcenter')
                    ->table('likes')
                    ->where('likeable_type', 'App\\Models\\Activity')
                    ->whereNotNull('user_id')
                    ->pluck('user_id')
            )
            ->unique();

        $interactorUsers = DB::connection('soulcenter')
            ->table('users')
            ->whereIn('id', $interactorIds)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'email', 'password', 'created_at')
            ->get();

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($interactorUsers, $existingEmails, &$imported, &$skipped) {
            foreach ($interactorUsers as $oldUser) {
                $email = strtolower(trim($oldUser->email));

                if ($existingEmails->has($email)) {
                    $skipped++;
                    continue;
                }

                $nameParts = $this->splitName($oldUser->name);

                DB::table('users')->insert([
                    'first_name' => $nameParts['first_name'],
                    'last_name' => $nameParts['last_name'],
                    'email' => $oldUser->email,
                    'password' => $oldUser->password,
                    'role' => 'member',
                    'email_verified_at' => now(),
                    'created_at' => $oldUser->created_at ?? now(),
                    'updated_at' => now(),
                ]);

                $existingEmails[$email] = true;
                $imported++;
            }
        });

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private function splitName(?string $name): array
    {
        if (empty($name)) {
            return ['first_name' => '', 'last_name' => ''];
        }

        $parts = explode(' ', trim($name), 2);
        return [
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? '',
        ];
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=ImportUsersTest
```

Expected: All 4 tests pass.

- [ ] **Step 5: Format code**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add app/Console/Commands/ImportUsers.php tests/Feature/ImportUsersTest.php
git commit -m "feat: add command to import users from CSV and old DB"
```

---

## Chunk 3: Re-link Authors + Import Comments

### Task 4: RelinkFicheAuthors command

**Files:**
- Create: `app/Console/Commands/RelinkFicheAuthors.php`
- Create: `tests/Feature/RelinkFicheAuthorsTest.php`

- [ ] **Step 1: Write the test**

Create `tests/Feature/RelinkFicheAuthorsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RelinkFicheAuthorsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Old DB tables
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS activities (
            id INT UNSIGNED PRIMARY KEY, title VARCHAR(255), published TINYINT DEFAULT 1, deleted_at TIMESTAMP NULL, created_at TIMESTAMP NULL
        )');
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS activity_author_profile (
            activity_id INT UNSIGNED, profile_id BIGINT UNSIGNED
        )');
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS profiles (
            id BIGINT UNSIGNED PRIMARY KEY, first_name VARCHAR(255), last_name VARCHAR(255),
            type VARCHAR(255), user_id INT UNSIGNED, carehome_id INT UNSIGNED
        )');
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS authors (
            id INT UNSIGNED PRIMARY KEY, name VARCHAR(255), company VARCHAR(255),
            email VARCHAR(255), user_id INT UNSIGNED, profile_id BIGINT UNSIGNED
        )');
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS users (
            id INT UNSIGNED PRIMARY KEY, email VARCHAR(255), name VARCHAR(255),
            password VARCHAR(100), created_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL
        )');
    }

    public function test_relinks_fiche_to_real_user_via_author_user_id(): void
    {
        $stubUser = User::factory()->create(['email' => 'stub@import.hartverwarmers.be', 'organisation' => 'Import']);
        $realUser = User::factory()->create(['email' => 'real@example.com']);
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create([
            'user_id' => $stubUser->id,
            'initiative_id' => $initiative->id,
            'migration_id' => 1000,
        ]);

        // Old: activity 1000 -> author -> user with email real@example.com
        DB::connection('soulcenter')->table('activities')->insert([
            'id' => 1000, 'title' => 'Test', 'published' => 1, 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('activity_author_profile')->insert([
            'activity_id' => 1000, 'profile_id' => 5000,
        ]);
        DB::connection('soulcenter')->table('profiles')->insert([
            'id' => 5000, 'first_name' => 'Real', 'last_name' => 'User', 'type' => 'author', 'user_id' => null, 'carehome_id' => null,
        ]);
        DB::connection('soulcenter')->table('authors')->insert([
            'id' => 1, 'name' => 'Real User', 'company' => 'WZC Test', 'email' => null, 'user_id' => 500, 'profile_id' => 5000,
        ]);
        DB::connection('soulcenter')->table('users')->insert([
            'id' => 500, 'email' => 'real@example.com', 'name' => 'Real User', 'password' => 'x', 'created_at' => now(),
        ]);

        $this->artisan('app:relink-fiche-authors')->assertSuccessful();

        $this->assertDatabaseHas('fiches', ['id' => $fiche->id, 'user_id' => $realUser->id]);
    }

    public function test_updates_stub_user_organisation_from_author_company(): void
    {
        $stubUser = User::factory()->create(['email' => 'stub@import.hartverwarmers.be', 'organisation' => 'Import']);
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create([
            'user_id' => $stubUser->id,
            'initiative_id' => $initiative->id,
            'migration_id' => 2000,
        ]);

        DB::connection('soulcenter')->table('activities')->insert([
            'id' => 2000, 'title' => 'Test2', 'published' => 1, 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('activity_author_profile')->insert([
            'activity_id' => 2000, 'profile_id' => 6000,
        ]);
        DB::connection('soulcenter')->table('profiles')->insert([
            'id' => 6000, 'first_name' => 'Stub', 'last_name' => 'Author', 'type' => 'author', 'user_id' => null, 'carehome_id' => null,
        ]);
        DB::connection('soulcenter')->table('authors')->insert([
            'id' => 2, 'name' => 'Stub Author', 'company' => 'WZC Zonnestraal', 'email' => null, 'user_id' => null, 'profile_id' => 6000,
        ]);

        $this->artisan('app:relink-fiche-authors')->assertSuccessful();

        // Fiche stays with stub user (no real user found), but org is updated
        $this->assertDatabaseHas('users', ['id' => $stubUser->id, 'organisation' => 'WZC Zonnestraal']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=RelinkFicheAuthors
```

- [ ] **Step 3: Create the command**

Create `app/Console/Commands/RelinkFicheAuthors.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RelinkFicheAuthors extends Command
{
    protected $signature = 'app:relink-fiche-authors';

    protected $description = 'Re-link fiches to real imported users and update stub user organisations';

    public function handle(): int
    {
        // Build email → new user ID map
        $newUsersByEmail = DB::table('users')
            ->pluck('id', 'email')
            ->mapWithKeys(fn ($id, $email) => [strtolower(trim($email)) => $id]);

        // Get all fiches with migration_id
        $fiches = DB::table('fiches')
            ->whereNotNull('migration_id')
            ->select('id', 'migration_id', 'user_id')
            ->get();

        $this->info("Processing {$fiches->count()} fiches with migration_id...");

        // Load author chain from old DB
        $authorData = DB::connection('soulcenter')
            ->table('activity_author_profile as aap')
            ->join('profiles as p', 'aap.profile_id', '=', 'p.id')
            ->join('authors as a', 'a.profile_id', '=', 'p.id')
            ->leftJoin('users as u', 'a.user_id', '=', 'u.id')
            ->select(
                'aap.activity_id',
                'a.email as author_email',
                'a.user_id as author_user_id',
                'a.company',
                'u.email as user_email'
            )
            ->get()
            // Activities can have multiple authors; take the first one per activity
            ->groupBy('activity_id')
            ->map(fn ($group) => $group->first());

        $relinked = 0;
        $orgUpdated = 0;
        $kept = 0;

        foreach ($fiches as $fiche) {
            $author = $authorData->get($fiche->migration_id);
            if (! $author) {
                $kept++;
                continue;
            }

            // Try to find real user email
            $email = null;
            if ($author->user_email) {
                $email = strtolower(trim($author->user_email));
            } elseif ($author->author_email) {
                $email = strtolower(trim(explode(',', $author->author_email)[0]));
            }

            $newUserId = $email ? ($newUsersByEmail[$email] ?? null) : null;

            if ($newUserId && $newUserId !== $fiche->user_id) {
                DB::table('fiches')->where('id', $fiche->id)->update(['user_id' => $newUserId]);
                $relinked++;
            } else {
                $kept++;

                // Update stub user's organisation if applicable
                $currentUser = DB::table('users')->where('id', $fiche->user_id)->first();
                if ($currentUser
                    && str_ends_with($currentUser->email, '@import.hartverwarmers.be')
                    && $author->company
                    && $currentUser->organisation === 'Import'
                ) {
                    DB::table('users')
                        ->where('id', $currentUser->id)
                        ->update(['organisation' => trim($author->company)]);
                    $orgUpdated++;
                }
            }
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Re-linked to real user', $relinked],
            ['Kept stub user', $kept],
            ['Stub orgs updated', $orgUpdated],
        ]);

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=RelinkFicheAuthors
```

Expected: All tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/RelinkFicheAuthors.php tests/Feature/RelinkFicheAuthorsTest.php
git commit -m "feat: add command to relink fiches to real users"
```

---

### Task 5: ImportComments command

**Files:**
- Create: `app/Console/Commands/ImportComments.php`
- Create: `tests/Feature/ImportCommentsTest.php`

- [ ] **Step 1: Write the test**

Create `tests/Feature/ImportCommentsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportCommentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS comments (
            id BIGINT UNSIGNED PRIMARY KEY,
            commentable_type VARCHAR(255),
            commentable_id BIGINT UNSIGNED,
            user_id BIGINT UNSIGNED,
            comment TEXT,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        )');
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS users (
            id INT UNSIGNED PRIMARY KEY,
            email VARCHAR(255),
            name VARCHAR(255),
            password VARCHAR(100),
            created_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        )');
    }

    public function test_imports_comment_with_correct_user_and_fiche(): void
    {
        $user = User::factory()->create(['email' => 'commenter@example.com']);
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create([
            'initiative_id' => $initiative->id,
            'migration_id' => 5000,
        ]);

        // Import user in old DB
        $importUser = User::factory()->create(['email' => 'import@hartverwarmers.be', 'first_name' => 'Hartverwarmers', 'last_name' => 'Import']);

        DB::connection('soulcenter')->table('users')->insert([
            'id' => 300, 'email' => 'commenter@example.com', 'name' => 'Test', 'password' => 'x', 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('comments')->insert([
            'id' => 1,
            'commentable_type' => 'App\\Models\\Activity',
            'commentable_id' => 5000,
            'user_id' => 300,
            'comment' => 'Heel leuk!',
            'created_at' => '2025-06-15 10:30:00',
            'updated_at' => '2025-06-15 10:30:00',
        ]);

        $this->artisan('app:import-comments')->assertSuccessful();

        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'App\\Models\\Fiche',
            'commentable_id' => $fiche->id,
            'user_id' => $user->id,
            'body' => 'Heel leuk!',
        ]);
    }

    public function test_skips_comments_with_empty_body(): void
    {
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create(['initiative_id' => $initiative->id, 'migration_id' => 6000]);

        User::factory()->create(['email' => 'import@hartverwarmers.be', 'first_name' => 'Hartverwarmers', 'last_name' => 'Import']);

        DB::connection('soulcenter')->table('users')->insert([
            'id' => 400, 'email' => 'test@example.com', 'name' => 'Test', 'password' => 'x', 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('comments')->insert([
            'id' => 2,
            'commentable_type' => 'App\\Models\\Activity',
            'commentable_id' => 6000,
            'user_id' => 400,
            'comment' => null,
            'created_at' => now(),
        ]);

        $this->artisan('app:import-comments')->assertSuccessful();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_skips_soft_deleted_comments(): void
    {
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create(['initiative_id' => $initiative->id, 'migration_id' => 7000]);

        User::factory()->create(['email' => 'import@hartverwarmers.be', 'first_name' => 'Hartverwarmers', 'last_name' => 'Import']);

        DB::connection('soulcenter')->table('users')->insert([
            'id' => 500, 'email' => 'test2@example.com', 'name' => 'Test', 'password' => 'x', 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('comments')->insert([
            'id' => 3,
            'commentable_type' => 'App\\Models\\Activity',
            'commentable_id' => 7000,
            'user_id' => 500,
            'comment' => 'Deleted comment',
            'created_at' => now(),
            'deleted_at' => now(),
        ]);

        $this->artisan('app:import-comments')->assertSuccessful();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_is_idempotent(): void
    {
        $user = User::factory()->create(['email' => 'repeat@example.com']);
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create([
            'initiative_id' => $initiative->id,
            'migration_id' => 8000,
        ]);

        User::factory()->create(['email' => 'import@hartverwarmers.be', 'first_name' => 'Hartverwarmers', 'last_name' => 'Import']);

        DB::connection('soulcenter')->table('users')->insert([
            'id' => 600, 'email' => 'repeat@example.com', 'name' => 'Test', 'password' => 'x', 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('comments')->insert([
            'id' => 4,
            'commentable_type' => 'App\\Models\\Activity',
            'commentable_id' => 8000,
            'user_id' => 600,
            'comment' => 'Twice!',
            'created_at' => '2025-09-01 10:00:00',
        ]);

        // Run twice
        $this->artisan('app:import-comments')->assertSuccessful();
        $this->artisan('app:import-comments')->assertSuccessful();

        // Should still only have 1 comment
        $this->assertDatabaseCount('comments', 1);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=ImportCommentsTest
```

- [ ] **Step 3: Create the command**

Create `app/Console/Commands/ImportComments.php`:

```php
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
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=ImportCommentsTest
```

Expected: All 3 tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/ImportComments.php tests/Feature/ImportCommentsTest.php
git commit -m "feat: add command to import comments from old DB"
```

---

## Chunk 4: Import Likes + Files + Cleanup

### Task 6: ImportLikes command

**Files:**
- Create: `app/Console/Commands/ImportLikes.php`
- Create: `tests/Feature/ImportLikesTest.php`

- [ ] **Step 1: Write the test**

Create `tests/Feature/ImportLikesTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportLikesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS likes (
            id BIGINT UNSIGNED PRIMARY KEY,
            user_id INT UNSIGNED,
            likeable_id INT UNSIGNED,
            likeable_type VARCHAR(255),
            profile_id BIGINT UNSIGNED,
            created_at TIMESTAMP NULL
        )');
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS users (
            id INT UNSIGNED PRIMARY KEY,
            email VARCHAR(255),
            name VARCHAR(255),
            password VARCHAR(100),
            created_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        )');
    }

    public function test_imports_like_as_kudos_type(): void
    {
        $user = User::factory()->create(['email' => 'liker@example.com']);
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create([
            'initiative_id' => $initiative->id,
            'migration_id' => 8000,
            'kudos_count' => 0,
        ]);

        DB::connection('soulcenter')->table('users')->insert([
            'id' => 600, 'email' => 'liker@example.com', 'name' => 'Liker', 'password' => 'x', 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('likes')->insert([
            'id' => 1, 'user_id' => 600, 'likeable_id' => 8000,
            'likeable_type' => 'App\\Models\\Activity', 'created_at' => '2025-03-01 12:00:00',
        ]);

        $this->artisan('app:import-likes')->assertSuccessful();

        $this->assertDatabaseHas('likes', [
            'likeable_type' => 'App\\Models\\Fiche',
            'likeable_id' => $fiche->id,
            'user_id' => $user->id,
            'type' => 'kudos',
            'count' => 1,
        ]);

        // Verify kudos_count was recalculated
        $this->assertDatabaseHas('fiches', [
            'id' => $fiche->id,
            'kudos_count' => 1,
        ]);
    }

    public function test_skips_likes_without_matching_user(): void
    {
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create([
            'initiative_id' => $initiative->id,
            'migration_id' => 9000,
        ]);

        // Like from user_id 999 who doesn't exist in new DB
        DB::connection('soulcenter')->table('users')->insert([
            'id' => 999, 'email' => 'ghost@example.com', 'name' => 'Ghost', 'password' => 'x', 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('likes')->insert([
            'id' => 2, 'user_id' => 999, 'likeable_id' => 9000,
            'likeable_type' => 'App\\Models\\Activity', 'created_at' => now(),
        ]);

        $this->artisan('app:import-likes')->assertSuccessful();

        $this->assertDatabaseCount('likes', 0);
    }

    public function test_skips_duplicate_likes(): void
    {
        $user = User::factory()->create(['email' => 'duper@example.com']);
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create([
            'initiative_id' => $initiative->id,
            'migration_id' => 10000,
        ]);

        DB::connection('soulcenter')->table('users')->insert([
            'id' => 700, 'email' => 'duper@example.com', 'name' => 'Duper', 'password' => 'x', 'created_at' => now(),
        ]);

        // Two likes from same user on same activity
        DB::connection('soulcenter')->table('likes')->insert([
            'id' => 3, 'user_id' => 700, 'likeable_id' => 10000,
            'likeable_type' => 'App\\Models\\Activity', 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('likes')->insert([
            'id' => 4, 'user_id' => 700, 'likeable_id' => 10000,
            'likeable_type' => 'App\\Models\\Activity', 'created_at' => now(),
        ]);

        $this->artisan('app:import-likes')->assertSuccessful();

        // Only one should be imported (unique constraint)
        $this->assertDatabaseCount('likes', 1);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=ImportLikesTest
```

- [ ] **Step 3: Create the command**

Create `app/Console/Commands/ImportLikes.php`:

```php
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
        DB::statement("
            UPDATE fiches SET kudos_count = (
                SELECT COALESCE(SUM(`count`), 0) FROM likes
                WHERE likeable_type = 'App\\\\Models\\\\Fiche'
                AND likeable_id = fiches.id
                AND type = 'kudos'
            )
        ");

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
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=ImportLikesTest
```

Expected: All 3 tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/ImportLikes.php tests/Feature/ImportLikesTest.php
git commit -m "feat: add command to import likes and recalculate kudos counts"
```

---

### Task 7: ImportFiles command

**Files:**
- Create: `app/Console/Commands/ImportFiles.php`
- Create: `tests/Feature/ImportFilesTest.php`

- [ ] **Step 1: Write the test**

Create `tests/Feature/ImportFilesTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Initiative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportFilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS media (
            id BIGINT UNSIGNED PRIMARY KEY,
            model_type VARCHAR(255),
            model_id BIGINT UNSIGNED,
            collection_name VARCHAR(255),
            name VARCHAR(255),
            file_name VARCHAR(255),
            mime_type VARCHAR(255),
            disk VARCHAR(255),
            size BIGINT UNSIGNED,
            order_column INT UNSIGNED,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )');
    }

    public function test_imports_file_record_for_existing_file(): void
    {
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create([
            'initiative_id' => $initiative->id,
            'migration_id' => 11000,
        ]);

        DB::connection('soulcenter')->table('media')->insert([
            'id' => 5140,
            'model_type' => 'App\\Models\\Activity',
            'model_id' => 11000,
            'collection_name' => 'downloads',
            'name' => 'algemene-quiz',
            'file_name' => 'algemene-quiz.pptx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'disk' => 'media',
            'size' => 44585940,
            'order_column' => 1,
            'created_at' => now(),
        ]);

        // Create the file on disk
        Storage::disk('public')->makeDirectory('files/media/5140');
        Storage::disk('public')->put('files/media/5140/algemene-quiz.pptx', 'fake content');

        $this->artisan('app:import-files')->assertSuccessful();

        $this->assertDatabaseHas('files', [
            'fiche_id' => $fiche->id,
            'original_filename' => 'algemene-quiz.pptx',
            'path' => 'files/media/5140/algemene-quiz.pptx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'size_bytes' => 44585940,
        ]);
    }

    public function test_skips_file_not_on_disk(): void
    {
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create([
            'initiative_id' => $initiative->id,
            'migration_id' => 12000,
        ]);

        DB::connection('soulcenter')->table('media')->insert([
            'id' => 9999,
            'model_type' => 'App\\Models\\Activity',
            'model_id' => 12000,
            'collection_name' => 'downloads',
            'name' => 'missing-file',
            'file_name' => 'missing-file.pdf',
            'mime_type' => 'application/pdf',
            'disk' => 'media',
            'size' => 1000,
            'order_column' => 1,
            'created_at' => now(),
        ]);

        // Do NOT create the file on disk

        $this->artisan('app:import-files')->assertSuccessful();

        $this->assertDatabaseCount('files', 0);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=ImportFilesTest
```

- [ ] **Step 3: Create the command**

Create `app/Console/Commands/ImportFiles.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportFiles extends Command
{
    protected $signature = 'app:import-files';

    protected $description = 'Import file records from old Soulcenter media table';

    public function handle(): int
    {
        $ficheMap = DB::table('fiches')
            ->whereNotNull('migration_id')
            ->pluck('id', 'migration_id');

        // Load old media records for published activities
        $mediaRecords = DB::connection('soulcenter')
            ->table('media')
            ->where('collection_name', 'downloads')
            ->where('model_type', 'like', '%Activity%')
            ->whereIn('model_id', $ficheMap->keys())
            ->select('id', 'model_id', 'file_name', 'mime_type', 'size', 'order_column', 'created_at')
            ->orderBy('model_id')
            ->orderBy('order_column')
            ->get();

        $this->info("Found {$mediaRecords->count()} media records to import");

        $imported = 0;
        $skippedNoFiche = 0;
        $skippedMissing = 0;
        $skippedExisting = 0;

        // Get existing file paths to avoid duplicates
        $existingPaths = DB::table('files')->pluck('path')->flip();

        DB::transaction(function () use ($mediaRecords, $ficheMap, $existingPaths, &$imported, &$skippedNoFiche, &$skippedMissing, &$skippedExisting) {
            foreach ($mediaRecords as $media) {
                $ficheId = $ficheMap[$media->model_id] ?? null;
                if (! $ficheId) {
                    $skippedNoFiche++;
                    continue;
                }

                $path = "files/media/{$media->id}/{$media->file_name}";

                // Skip if already imported
                if ($existingPaths->has($path)) {
                    $skippedExisting++;
                    continue;
                }

                // Verify file exists on disk
                if (! Storage::disk('public')->exists($path)) {
                    $this->warn("Missing file: {$path}");
                    $skippedMissing++;
                    continue;
                }

                DB::table('files')->insert([
                    'fiche_id' => $ficheId,
                    'original_filename' => $media->file_name,
                    'path' => $path,
                    'mime_type' => $media->mime_type,
                    'size_bytes' => $media->size,
                    'sort_order' => $media->order_column ?? 0,
                    'created_at' => $media->created_at ?? now(),
                    'updated_at' => $media->created_at ?? now(),
                ]);

                $existingPaths[$path] = true;
                $imported++;
            }
        });

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Imported', $imported],
            ['Skipped (no matching fiche)', $skippedNoFiche],
            ['Skipped (file missing on disk)', $skippedMissing],
            ['Skipped (already imported)', $skippedExisting],
        ]);

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=ImportFilesTest
```

Expected: All 2 tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/ImportFiles.php tests/Feature/ImportFilesTest.php
git commit -m "feat: add command to import file records from old media table"
```

---

### Task 8: CleanupMedia command

**Files:**
- Create: `app/Console/Commands/CleanupMedia.php`

- [ ] **Step 1: Create the command**

Create `app/Console/Commands/CleanupMedia.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CleanupMedia extends Command
{
    protected $signature = 'app:cleanup-media {--force : Actually delete orphaned folders}';

    protected $description = 'List or remove media folders not linked to any imported file';

    public function handle(): int
    {
        $force = $this->option('force');

        // Get all media IDs that are used by imported files
        $usedMediaIds = DB::table('files')
            ->whereNotNull('fiche_id')
            ->pluck('path')
            ->map(function ($path) {
                // Extract media ID from path like "files/media/5140/file.pptx"
                if (preg_match('#files/media/(\d+)/#', $path, $matches)) {
                    return $matches[1];
                }
                return null;
            })
            ->filter()
            ->flip();

        // List all folders in files/media/
        $allFolders = Storage::disk('public')->directories('files/media');
        $orphaned = [];

        foreach ($allFolders as $folder) {
            $mediaId = basename($folder);
            if (! $usedMediaIds->has($mediaId)) {
                $orphaned[] = $folder;
            }
        }

        $this->info("Total media folders: " . count($allFolders));
        $this->info("Used by imported files: {$usedMediaIds->count()}");
        $this->info("Orphaned: " . count($orphaned));

        if (empty($orphaned)) {
            $this->info('No orphaned folders found.');
            return self::SUCCESS;
        }

        if (! $force) {
            $this->warn('Dry run — use --force to actually delete. Showing first 20 orphaned:');
            foreach (array_slice($orphaned, 0, 20) as $folder) {
                $this->line("  {$folder}");
            }
            if (count($orphaned) > 20) {
                $this->line("  ... and " . (count($orphaned) - 20) . " more");
            }
            return self::SUCCESS;
        }

        if (! $this->confirm("Delete " . count($orphaned) . " orphaned media folders?")) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($orphaned as $folder) {
            Storage::disk('public')->deleteDirectory($folder);
            $deleted++;
        }

        $this->info("Deleted {$deleted} orphaned media folders.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Console/Commands/CleanupMedia.php
git commit -m "feat: add command to list/remove orphaned media folders"
```

---

### Task 9: Run Pint + Full test suite

- [ ] **Step 1: Format all PHP files**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 2: Run full test suite**

```bash
php artisan test --compact
```

Expected: All tests pass (existing + new migration tests).

- [ ] **Step 3: Commit any formatting fixes**

```bash
git add -A && git commit -m "style: format code with Pint"
```

---

### Task 10: Execute the migration commands against real data

Run each command in order. Verify counts after each step.

- [ ] **Step 1: Map migration IDs**

```bash
php artisan app:map-migration-ids
```

Verify: `php artisan tinker --execute="echo App\Models\Fiche::whereNotNull('migration_id')->count();"`
Expected: ~394

- [ ] **Step 2: Import users**

```bash
php artisan app:import-users /Users/frederikvincx/Downloads/hartverwarmers/contacts-1773584301514.csv
```

Verify: `php artisan tinker --execute="echo App\Models\User::count();"`
Expected: ~4,900+ (existing + CSV + interactors)

- [ ] **Step 3: Re-link fiche authors**

```bash
php artisan app:relink-fiche-authors
```

Verify: Check output table for re-linked count (~171) and org updates.

- [ ] **Step 4: Import comments**

```bash
php artisan app:import-comments
```

Verify: `php artisan tinker --execute="echo App\Models\Comment::count();"`
Expected: ~376 (5 existing + ~371 imported)

- [ ] **Step 5: Import likes**

```bash
php artisan app:import-likes
```

Verify: `php artisan tinker --execute="echo App\Models\Like::where('type','kudos')->count();"`
Expected: several thousand

- [ ] **Step 6: Import files**

```bash
php artisan app:import-files
```

Verify: `php artisan tinker --execute="echo App\Models\File::count();"`
Expected: ~653 (35 existing + ~618 imported)

- [ ] **Step 7: Verify icons preserved**

```bash
php artisan tinker --execute="echo App\Models\Fiche::whereNotNull('icon')->count();"
```

Expected: 393+ (same as before migration)

- [ ] **Step 8: Review media cleanup**

```bash
php artisan app:cleanup-media --dry-run
```

Review orphaned count. Only run `--force` after manual review.

- [ ] **Step 9: Commit the spec and plan docs**

```bash
git add docs/superpowers/
git commit -m "docs: add data migration spec and implementation plan"
```
