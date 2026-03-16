# Pre-generate ZIP Downloads Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Pre-generate ZIP archives for fiches with multiple files so downloads are instant instead of zipping 50-200 MB on every request.

**Architecture:** Add a `zip_path` column to `fiches`. A new Artisan command pre-generates ZIPs. The download controller serves the pre-built ZIP directly. ZIPs are regenerated when files change (via Fiche model observer or a simple command re-run). Single-file fiches continue to serve the file directly — no ZIP needed.

**Tech Stack:** Laravel 12, PHP ZipArchive

---

## Scope

- **97 fiches** have multiple downloadable files (non-generated, original uploads)
- Total size: ~1.15 GB across those fiches
- Largest fiche: 199 MB (4 files)
- ZIPs use `ZipArchive::CM_STORE` (no compression) since most files are already compressed (PPTX, PDF, JPEG) — fast creation, no CPU waste

---

## File Structure

### New files
- `database/migrations/YYYY_MM_DD_HHMMSS_add_zip_path_to_fiches_table.php`
- `app/Console/Commands/GenerateFicheZips.php`
- `tests/Feature/GenerateFicheZipsTest.php`

### Modified files
- `app/Models/Fiche.php` — add `zip_path` to `$fillable`
- `app/Http/Controllers/FicheController.php` — serve pre-built ZIP instead of generating on-the-fly

---

## Task 1: Migration + GenerateFicheZips command

**Files:**
- Create: `database/migrations/..._add_zip_path_to_fiches_table.php`
- Create: `app/Console/Commands/GenerateFicheZips.php`
- Create: `tests/Feature/GenerateFicheZipsTest.php`
- Modify: `app/Models/Fiche.php`

- [ ] **Step 1: Create the migration**

```bash
php artisan make:migration add_zip_path_to_fiches_table --table=fiches --no-interaction
```

Contents:

```php
return new class extends Migration {
    public function up(): void
    {
        Schema::table('fiches', function (Blueprint $table) {
            $table->string('zip_path')->nullable()->after('migration_id');
        });
    }

    public function down(): void
    {
        Schema::table('fiches', function (Blueprint $table) {
            $table->dropColumn('zip_path');
        });
    }
};
```

- [ ] **Step 2: Add `zip_path` to Fiche model $fillable**

In `app/Models/Fiche.php`, add `'zip_path'` to the `$fillable` array after `'migration_id'`.

- [ ] **Step 3: Run migration**

```bash
php artisan migrate
```

- [ ] **Step 4: Write the test**

Create `tests/Feature/GenerateFicheZipsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\File;
use App\Models\Initiative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateFicheZipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_zip_for_fiche_with_multiple_files(): void
    {
        Storage::fake('public');

        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Storage::disk('public')->put('files/a.pdf', 'content-a');
        Storage::disk('public')->put('files/b.pdf', 'content-b');

        File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'document-a.pdf',
            'path' => 'files/a.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 9,
            'sort_order' => 0,
        ]);
        File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'document-b.pdf',
            'path' => 'files/b.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 9,
            'sort_order' => 1,
        ]);

        $this->artisan('app:generate-zips')->assertSuccessful();

        $fiche->refresh();
        $this->assertNotNull($fiche->zip_path);
        $this->assertTrue(Storage::disk('public')->exists($fiche->zip_path));
    }

    public function test_skips_fiche_with_single_file(): void
    {
        Storage::fake('public');

        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Storage::disk('public')->put('files/only.pdf', 'content');

        File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'only.pdf',
            'path' => 'files/only.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 7,
            'sort_order' => 0,
        ]);

        $this->artisan('app:generate-zips')->assertSuccessful();

        $fiche->refresh();
        $this->assertNull($fiche->zip_path);
    }

    public function test_excludes_generated_pdfs_from_zip(): void
    {
        Storage::fake('public');

        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Storage::disk('public')->put('files/slides.pptx', 'pptx-content');
        Storage::disk('public')->put('files/slides.pdf', 'pdf-content');

        $pptx = File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'slides.pptx',
            'path' => 'files/slides.pptx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'size_bytes' => 12,
            'sort_order' => 0,
        ]);
        // Generated PDF — should NOT be included in ZIP
        File::create([
            'fiche_id' => $fiche->id,
            'source_file_id' => $pptx->id,
            'original_filename' => 'slides.pdf',
            'path' => 'files/slides.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 11,
            'sort_order' => 1,
        ]);

        $this->artisan('app:generate-zips')->assertSuccessful();

        $fiche->refresh();
        // Only 1 original file → no ZIP needed
        $this->assertNull($fiche->zip_path);
    }

    public function test_regenerates_zip_when_force_flag_used(): void
    {
        Storage::fake('public');

        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'zip_path' => 'fiche-zips/old.zip',
        ]);

        Storage::disk('public')->put('files/a.pdf', 'content-a');
        Storage::disk('public')->put('files/b.pdf', 'content-b');
        Storage::disk('public')->put('fiche-zips/old.zip', 'old-zip');

        File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'a.pdf',
            'path' => 'files/a.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 9,
            'sort_order' => 0,
        ]);
        File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'b.pdf',
            'path' => 'files/b.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 9,
            'sort_order' => 1,
        ]);

        $this->artisan('app:generate-zips', ['--force' => true])->assertSuccessful();

        $fiche->refresh();
        $this->assertNotEquals('fiche-zips/old.zip', $fiche->zip_path);
        $this->assertTrue(Storage::disk('public')->exists($fiche->zip_path));
    }
}
```

- [ ] **Step 5: Run tests to verify they fail**

```bash
php artisan test --compact --filter=GenerateFicheZips
```

- [ ] **Step 6: Write the command**

Create `app/Console/Commands/GenerateFicheZips.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\Fiche;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class GenerateFicheZips extends Command
{
    protected $signature = 'app:generate-zips
                            {--force : Regenerate existing ZIPs}
                            {--fiche= : Process a specific fiche ID}';

    protected $description = 'Pre-generate ZIP archives for fiches with multiple downloadable files';

    public function handle(): int
    {
        $query = Fiche::whereHas('files', function ($q) {
            $q->whereNull('source_file_id'); // Only original files, not generated PDFs
        })->with(['files' => function ($q) {
            $q->whereNull('source_file_id')->orderBy('sort_order');
        }]);

        if ($ficheId = $this->option('fiche')) {
            $query->where('id', $ficheId);
        }

        $fiches = $query->get();

        $generated = 0;
        $skipped = 0;
        $singleFile = 0;

        foreach ($fiches as $fiche) {
            $downloadableFiles = $fiche->files;

            // No ZIP needed for single file
            if ($downloadableFiles->count() <= 1) {
                $singleFile++;

                // Clear stale zip_path if files were removed
                if ($fiche->zip_path) {
                    $this->deleteOldZip($fiche->zip_path);
                    $fiche->update(['zip_path' => null]);
                }

                continue;
            }

            // Skip if ZIP already exists (unless --force)
            if ($fiche->zip_path && ! $this->option('force')) {
                if (Storage::disk('public')->exists($fiche->zip_path)) {
                    $skipped++;
                    continue;
                }
            }

            // Delete old ZIP if regenerating
            if ($fiche->zip_path) {
                $this->deleteOldZip($fiche->zip_path);
            }

            // Create ZIP
            $zipRelativePath = "fiche-zips/{$fiche->slug}.zip";
            $zipAbsolutePath = Storage::disk('public')->path($zipRelativePath);

            // Ensure directory exists
            Storage::disk('public')->makeDirectory('fiche-zips');

            $zip = new ZipArchive;
            if ($zip->open($zipAbsolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                $this->warn("Failed to create ZIP for fiche {$fiche->id}: {$fiche->title}");
                continue;
            }

            $hasFiles = false;
            foreach ($downloadableFiles as $file) {
                $filePath = Storage::disk('public')->path($file->path);
                if (file_exists($filePath)) {
                    // Store without compression (files are already compressed)
                    $zip->addFile($filePath, $file->original_filename);
                    $zip->setCompressionName($file->original_filename, ZipArchive::CM_STORE);
                    $hasFiles = true;
                } else {
                    $this->warn("  Missing file: {$file->path}");
                }
            }

            $zip->close();

            if ($hasFiles) {
                $fiche->update(['zip_path' => $zipRelativePath]);
                $generated++;
                $this->line("  ZIP: {$fiche->title} ({$downloadableFiles->count()} files)");
            } else {
                // Clean up empty ZIP
                Storage::disk('public')->delete($zipRelativePath);
            }
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['ZIPs generated', $generated],
            ['Already exists (skipped)', $skipped],
            ['Single file (no ZIP)', $singleFile],
        ]);

        return self::SUCCESS;
    }

    private function deleteOldZip(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
```

- [ ] **Step 7: Run tests**

```bash
php artisan test --compact --filter=GenerateFicheZips
```

Expected: All 4 tests pass.

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/*zip_path* app/Models/Fiche.php app/Console/Commands/GenerateFicheZips.php tests/Feature/GenerateFicheZipsTest.php
git commit -m "feat: add pre-generated ZIP downloads for multi-file fiches"
```

---

## Task 2: Update download controller to serve pre-built ZIPs

**Files:**
- Modify: `app/Http/Controllers/FicheController.php:104-118`

- [ ] **Step 1: Modify the download controller**

Replace the on-the-fly ZIP creation block (lines 104-118) with logic that serves the pre-built ZIP:

```php
// Multi-file: serve pre-built ZIP or fall back to on-the-fly
if ($fiche->zip_path && Storage::disk('public')->exists($fiche->zip_path)) {
    return response()->download(
        Storage::disk('public')->path($fiche->zip_path),
        $fiche->slug . '-bestanden.zip',
    );
}

// Fallback: generate on-the-fly (for fiches without pre-built ZIP)
$tempPath = tempnam(sys_get_temp_dir(), 'fiche-zip-');
$zip = new ZipArchive;
$zip->open($tempPath, ZipArchive::OVERWRITE);

foreach ($files as $file) {
    $zip->addFile(Storage::disk('public')->path($file->path), $file->original_filename);
}

$zip->close();

return response()->download($tempPath, $fiche->slug . '-bestanden.zip', [
    'Content-Type' => 'application/zip',
])->deleteFileAfterSend();
```

- [ ] **Step 2: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/FicheController.php
git commit -m "feat: serve pre-built ZIP downloads, fall back to on-the-fly"
```

---

## Task 3: Execute ZIP generation

- [ ] **Step 1: Run for all fiches**

```bash
php artisan app:generate-zips
```

Expected: ~97 ZIPs generated.

- [ ] **Step 2: Verify**

```bash
php artisan tinker --execute="echo App\Models\Fiche::whereNotNull('zip_path')->count();"
```

Expected: ~97

- [ ] **Step 3: Check disk usage**

```bash
du -sh storage/app/public/fiche-zips/
```
