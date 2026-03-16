# Backfill File Processing Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Generate PDF versions, preview images, and text extraction for all 616 imported files so they display properly with slide previews on fiche pages.

**Architecture:** A single Artisan command `app:backfill-files` that orchestrates the three processing stages in order: (1) link existing PDF twins, (2) generate missing PDFs, (3) generate previews. Uses the existing `PdfConverter` and `GenerateFilePreviewsCommand` infrastructure. ZIP downloads already work on-demand — no pre-generation needed.

**Tech Stack:** Laravel 12, LibreOffice (soffice), Imagick, PHP ZipArchive

---

## Current State

| File Type | Count | Has Previews | Notes |
|-----------|-------|-------------|-------|
| PDF | 262 | 2 | Standalone or author-uploaded twin |
| DOCX | 175 | 0 | Need PDF conversion + previews |
| PPTX | 100 | 5 | Need PDF conversion + previews |
| JPEG | 63 | 0 | Need preview (resize) |
| PNG | 20 | 0 | Need preview (resize) |
| Other (zip, mp3, xlsx, etc.) | 24 | 0 | Cannot preview — skip |

**PDF twins found:** 14 (author uploaded both PPTX + PDF with same base filename)
**Convertible files without twin:** 258 (need LibreOffice conversion)

---

## File Structure

### New files
- `app/Console/Commands/BackfillFileProcessing.php` — orchestrator command
- `tests/Feature/BackfillFileProcessingTest.php`

---

## Task 1: BackfillFileProcessing command

**Files:**
- Create: `app/Console/Commands/BackfillFileProcessing.php`
- Create: `tests/Feature/BackfillFileProcessingTest.php`

### Processing stages (in order)

**Stage 1: Link existing PDF twins**

For each fiche, find PPTX/DOCX files that have a matching PDF (same base filename). Set `source_file_id` on the PDF to point to the PPTX/DOCX. This tells the preview generator to use the existing PDF instead of converting.

```
files where: same fiche_id, same basename, one is PPTX/DOCX, one is PDF
→ PDF.source_file_id = PPTX.id
```

**Stage 2: Generate PDF versions for convertible files without twins**

For files where `isConvertibleToPdf()` is true AND no `pdfVersion` relationship exists (no linked PDF), generate one using `PdfConverter::convertAndStore()`.

Skip files that already have a PDF twin (linked in Stage 1).

This is essentially what `file:generate-pdf-versions` does, but we need to run it for all imported files.

**Stage 3: Generate preview images**

For all files (PDFs, images, PPTX via their PDF version), generate preview images using the existing `file:generate-previews` command logic.

Skip files that already have `preview_images` set (idempotent).

### Command implementation

- [ ] **Step 1: Write the test**

Create `tests/Feature/BackfillFileProcessingTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\File;
use App\Models\Initiative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackfillFileProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_links_pdf_twins_by_matching_base_filename(): void
    {
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $pptx = File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'presentation.pptx',
            'path' => 'files/test/presentation.pptx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'size_bytes' => 1000,
            'sort_order' => 0,
        ]);

        $pdf = File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'presentation.pdf',
            'path' => 'files/test/presentation.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 500,
            'sort_order' => 1,
        ]);

        $this->artisan('app:backfill-files', ['--link-twins-only' => true])
            ->assertSuccessful();

        $pdf->refresh();
        $this->assertEquals($pptx->id, $pdf->source_file_id);
    }

    public function test_does_not_link_pdfs_with_different_base_filename(): void
    {
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $pptx = File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'slides.pptx',
            'path' => 'files/test/slides.pptx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'size_bytes' => 1000,
            'sort_order' => 0,
        ]);

        $pdf = File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'handout.pdf',
            'path' => 'files/test/handout.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 500,
            'sort_order' => 1,
        ]);

        $this->artisan('app:backfill-files', ['--link-twins-only' => true])
            ->assertSuccessful();

        $pdf->refresh();
        $this->assertNull($pdf->source_file_id);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=BackfillFileProcessing
```

- [ ] **Step 3: Write the command**

Create `app/Console/Commands/BackfillFileProcessing.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Services\PdfConverter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class BackfillFileProcessing extends Command
{
    protected $signature = 'app:backfill-files
                            {--link-twins-only : Only link PDF twins, skip PDF generation and previews}
                            {--skip-previews : Skip preview generation}
                            {--file= : Process a specific file ID only}';

    protected $description = 'Backfill PDF versions and preview images for imported files';

    private const CONVERTIBLE_TYPES = [
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword',
        'application/vnd.ms-powerpoint',
        'application/vnd.oasis.opendocument.presentation',
    ];

    public function handle(): int
    {
        // Stage 1: Link existing PDF twins
        $this->info('Stage 1: Linking PDF twins...');
        $linked = $this->linkPdfTwins();
        $this->info("Linked {$linked} PDF twins");

        if ($this->option('link-twins-only')) {
            return self::SUCCESS;
        }

        // Stage 2: Generate missing PDF versions
        $this->newLine();
        $this->info('Stage 2: Generating PDF versions for convertible files...');
        $generated = $this->generateMissingPdfs();
        $this->info("Generated {$generated} PDF versions");

        if ($this->option('skip-previews')) {
            return self::SUCCESS;
        }

        // Stage 3: Generate preview images
        $this->newLine();
        $this->info('Stage 3: Generating preview images...');
        $previewed = $this->generatePreviews();
        $this->info("Generated previews for {$previewed} files");

        $this->newLine();
        $this->table(['Stage', 'Count'], [
            ['PDF twins linked', $linked],
            ['PDFs generated', $generated],
            ['Previews generated', $previewed],
        ]);

        return self::SUCCESS;
    }

    private function linkPdfTwins(): int
    {
        $files = File::whereNotNull('fiche_id')->get();
        $byFiche = $files->groupBy('fiche_id');
        $linked = 0;

        foreach ($byFiche as $ficheFiles) {
            $convertibles = $ficheFiles->filter(fn (File $f) => in_array($f->mime_type, self::CONVERTIBLE_TYPES));
            $pdfs = $ficheFiles->filter(fn (File $f) => $f->mime_type === 'application/pdf' && $f->source_file_id === null);

            foreach ($convertibles as $conv) {
                $baseName = pathinfo($conv->original_filename, PATHINFO_FILENAME);
                $matchingPdf = $pdfs->first(fn (File $p) => pathinfo($p->original_filename, PATHINFO_FILENAME) === $baseName);

                if ($matchingPdf) {
                    $matchingPdf->update(['source_file_id' => $conv->id]);
                    $linked++;
                    $this->line("  Linked: {$conv->original_filename} → {$matchingPdf->original_filename}");
                }
            }
        }

        return $linked;
    }

    private function generateMissingPdfs(): int
    {
        $fileId = $this->option('file');

        $query = File::whereNull('source_file_id')
            ->whereNotNull('fiche_id')
            ->whereIn('mime_type', self::CONVERTIBLE_TYPES)
            ->whereDoesntHave('pdfVersion');

        if ($fileId) {
            $query->where('id', $fileId);
        }

        $files = $query->get();
        $this->info("Found {$files->count()} files needing PDF conversion");

        $converter = app(PdfConverter::class);
        $generated = 0;
        $failed = 0;

        foreach ($files as $file) {
            $this->line("  Converting: {$file->original_filename}...");

            try {
                $pdf = $converter->convertAndStore($file);
                if ($pdf) {
                    $generated++;
                } else {
                    $failed++;
                    $this->warn("  Failed: {$file->original_filename}");
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->warn("  Error: {$file->original_filename} — {$e->getMessage()}");
            }
        }

        if ($failed > 0) {
            $this->warn("{$failed} files failed PDF conversion");
        }

        return $generated;
    }

    private function generatePreviews(): int
    {
        $fileId = $this->option('file');

        $query = File::whereNotNull('fiche_id')
            ->whereNull('preview_images');

        if ($fileId) {
            $query->where('id', $fileId);
        }

        // Only process previewable types (PDF, images, and convertible docs that now have a PDF)
        $previewableTypes = array_merge(
            ['application/pdf', 'image/jpeg', 'image/png'],
            self::CONVERTIBLE_TYPES,
        );
        $query->whereIn('mime_type', $previewableTypes);

        // Exclude generated PDFs (they're previewed via their source)
        $query->whereNull('source_file_id');

        $files = $query->get();
        $this->info("Found {$files->count()} files needing previews");

        $generated = 0;

        foreach ($files as $file) {
            $this->line("  Previewing: {$file->original_filename}...");

            try {
                Artisan::call('file:generate-previews', ['--file' => $file->id]);
                $file->refresh();
                if ($file->preview_images) {
                    $generated++;
                }
            } catch (\Throwable $e) {
                $this->warn("  Error: {$file->original_filename} — {$e->getMessage()}");
            }
        }

        return $generated;
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=BackfillFileProcessing
```

Expected: Both tests pass.

- [ ] **Step 5: Format with Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add app/Console/Commands/BackfillFileProcessing.php tests/Feature/BackfillFileProcessingTest.php
git commit -m "feat: add command to backfill PDF versions and previews for imported files"
```

---

## Task 2: Execute backfill

Run the command in stages to monitor progress and catch issues early.

- [ ] **Step 1: Link PDF twins only**

```bash
php artisan app:backfill-files --link-twins-only
```

Expected: ~14 PDF twins linked.

- [ ] **Step 2: Test PDF generation on a single file**

Pick one PPTX file and test conversion:

```bash
php artisan app:backfill-files --skip-previews --file={id}
```

Verify the PDF was created in storage and the File record has `source_file_id`.

- [ ] **Step 3: Generate all PDFs (skip previews first)**

```bash
php artisan app:backfill-files --skip-previews
```

This will convert ~258 PPTX/DOCX files using LibreOffice. This may take a while (~1-2 min per file for large PPTX). Monitor for failures.

- [ ] **Step 4: Generate all previews**

```bash
php artisan app:backfill-files
```

Since PDFs are already generated, this will only run Stage 3 (preview generation).

- [ ] **Step 5: Verify results**

```bash
php artisan tinker --execute="
    echo 'Files with previews: ' . App\Models\File::whereNotNull('preview_images')->count() . PHP_EOL;
    echo 'Files with PDF version: ' . App\Models\File::whereNotNull('source_file_id')->count() . PHP_EOL;
    echo 'Total files: ' . App\Models\File::count() . PHP_EOL;
"
```

---

## Notes

**ZIP downloads:** Already work on-demand in `FicheController::downloadFiles()` — no pre-generation needed. When a user downloads multiple files from a fiche, a ZIP is created on the fly.

**Skipped file types:** Audio (mp3), archives (zip), spreadsheets (xlsx), HTML, and octet-stream files cannot be previewed — they'll remain without preview images, which is fine.

**LibreOffice requirement:** The `soffice` binary must be available. On macOS with Herd: `/Applications/LibreOffice.app/Contents/MacOS/soffice`.

**Idempotent:** The command can be re-run safely — it skips already-linked twins, already-generated PDFs, and files with existing previews.
