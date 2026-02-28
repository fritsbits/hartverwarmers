<?php

namespace App\Console\Commands;

use App\Models\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Imagick;

class GenerateFilePreviewsCommand extends Command
{
    protected $signature = 'file:generate-previews
        {--file= : Specific file ID}
        {--all : Process all files without previews}
        {--max-slides=8 : Maximum number of slide previews to generate}';

    protected $description = 'Generate preview images for uploaded files (PPTX, PDF)';

    public function handle(): int
    {
        $maxSlides = (int) $this->option('max-slides');

        if ($this->option('file')) {
            $file = File::find($this->option('file'));

            if (! $file) {
                $this->error('File not found.');

                return self::FAILURE;
            }

            return $this->processFile($file, $maxSlides) ? self::SUCCESS : self::FAILURE;
        }

        if ($this->option('all')) {
            $files = File::whereNull('preview_images')->get();
            $this->info("Processing {$files->count()} files...");

            $success = 0;
            foreach ($files as $file) {
                if ($this->processFile($file, $maxSlides)) {
                    $success++;
                }
            }

            $this->info("Generated previews for {$success}/{$files->count()} files.");

            return self::SUCCESS;
        }

        $this->error('Please specify --file=ID or --all');

        return self::FAILURE;
    }

    private function processFile(File $file, int $maxSlides): bool
    {
        $this->info("Processing: {$file->original_filename} (ID: {$file->id})");

        $storagePath = Storage::disk('public')->path($file->path);

        if (! file_exists($storagePath)) {
            $this->error("  File not found on disk: {$storagePath}");

            return false;
        }

        $isPptx = str_contains($file->mime_type, 'presentation') || str_contains($file->mime_type, 'powerpoint');
        $isPdf = str_contains($file->mime_type, 'pdf');

        if (! $isPptx && ! $isPdf) {
            $this->warn("  Skipping: unsupported file type ({$file->mime_type})");

            return false;
        }

        $pdfPath = $storagePath;

        if ($isPptx) {
            $pdfPath = $this->convertPptxToPdf($storagePath);

            if (! $pdfPath) {
                return false;
            }
        }

        $previewPaths = $this->generateSlideImages($pdfPath, $file->id, $maxSlides);

        if ($isPptx && $pdfPath !== $storagePath) {
            @unlink($pdfPath);
        }

        if (empty($previewPaths)) {
            $this->error('  No preview images generated.');

            return false;
        }

        $file->update(['preview_images' => $previewPaths]);
        $this->info('  Generated '.count($previewPaths).' preview images.');

        return true;
    }

    private function convertPptxToPdf(string $pptxPath): ?string
    {
        $outputDir = sys_get_temp_dir();
        $basename = pathinfo($pptxPath, PATHINFO_FILENAME);

        $command = sprintf(
            'soffice --headless --convert-to pdf --outdir %s %s 2>&1',
            escapeshellarg($outputDir),
            escapeshellarg($pptxPath)
        );

        $this->info('  Converting PPTX to PDF...');
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            $this->error('  LibreOffice conversion failed: '.implode("\n", $output));

            return null;
        }

        $pdfPath = $outputDir.'/'.$basename.'.pdf';

        if (! file_exists($pdfPath)) {
            $this->error('  PDF output not found at: '.$pdfPath);

            return null;
        }

        $this->info('  PDF created successfully.');

        return $pdfPath;
    }

    /**
     * @return string[]
     */
    private function generateSlideImages(string $pdfPath, int $fileId, int $maxSlides): array
    {
        $this->info('  Generating slide images from PDF...');

        $previewDir = "file-previews/{$fileId}";
        Storage::disk('public')->makeDirectory($previewDir);

        $paths = [];

        $imagick = new Imagick;
        $imagick->setResolution(200, 200);

        $pageCount = $imagick->pingImage($pdfPath);
        $totalPages = $imagick->getNumberImages();
        $pagesToProcess = min($totalPages, $maxSlides);

        $this->info("  Found {$totalPages} pages, processing {$pagesToProcess}...");

        for ($i = 0; $i < $pagesToProcess; $i++) {
            $im = new Imagick;
            $im->setResolution(200, 200);
            $im->readImage("{$pdfPath}[{$i}]");
            $im->setImageFormat('jpeg');
            $im->setImageCompressionQuality(85);

            $width = $im->getImageWidth();
            $height = $im->getImageHeight();

            if ($width > 1280) {
                $im->resizeImage(1280, 0, Imagick::FILTER_LANCZOS, 1);
            }

            $slideNum = str_pad($i + 1, 3, '0', STR_PAD_LEFT);
            $relativePath = "{$previewDir}/slide-{$slideNum}.jpg";
            $absolutePath = Storage::disk('public')->path($relativePath);

            $im->writeImage($absolutePath);
            $im->destroy();

            $paths[] = $relativePath;
        }

        return $paths;
    }
}
