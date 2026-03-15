<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Services\PdfConverter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

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
