<?php

namespace App\Console\Commands;

use App\Concerns\ConvertsDocumentsViaSoffice;
use App\Models\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Imagick;

class GenerateFilePreviewsCommand extends Command
{
    use ConvertsDocumentsViaSoffice;

    protected $signature = 'file:generate-previews
        {--file= : Specific file ID}
        {--all : Process all files without previews}
        {--max-slides=8 : Maximum number of slide previews to generate}';

    protected $description = 'Generate preview images for uploaded files (PPTX, DOCX, PDF, images)';

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
            $files = File::where(function ($q) {
                $q->whereNull('preview_images')->orWhereNull('total_slides');
            })->get();
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
        $isDocx = str_contains($file->mime_type, 'word') || str_contains($file->mime_type, 'document');
        $isPdf = str_contains($file->mime_type, 'pdf');
        $isImage = str_starts_with($file->mime_type, 'image/');

        if (! $isPptx && ! $isDocx && ! $isPdf && ! $isImage) {
            $this->warn("  Skipping: unsupported file type ({$file->mime_type})");

            return false;
        }

        try {
            if ($isImage) {
                return $this->processImage($storagePath, $file);
            }

            $pdfPath = $storagePath;

            if ($isPptx || $isDocx) {
                $pdfPath = $this->convertToPdf($storagePath);

                if (! $pdfPath) {
                    return false;
                }
            }

            $result = $this->generateSlideImages($pdfPath, $file->id, $maxSlides);

            if (($isPptx || $isDocx) && $pdfPath !== $storagePath) {
                @unlink($pdfPath);
            }

            if (empty($result['paths'])) {
                $this->error('  No preview images generated.');

                return false;
            }

            $file->update([
                'preview_images' => $result['paths'],
                'total_slides' => $result['totalPages'],
            ]);
            $this->info('  Generated '.count($result['paths']).' preview images (total: '.$result['totalPages'].' pages).');

            return true;
        } catch (\Throwable $e) {
            $this->error("  Failed: {$e->getMessage()}");
            report($e);

            return false;
        }
    }

    private function processImage(string $storagePath, File $file): bool
    {
        $this->info('  Processing image...');

        $previewDir = "file-previews/{$file->id}";
        Storage::disk('public')->makeDirectory($previewDir);

        $relativePath = "{$previewDir}/slide-001.jpg";
        $absolutePath = Storage::disk('public')->path($relativePath);

        $im = new Imagick($storagePath);
        $im->setImageFormat('jpeg');
        $im->setImageCompressionQuality(85);

        $width = $im->getImageWidth();
        if ($width > 1280) {
            $im->resizeImage(1280, 0, Imagick::FILTER_LANCZOS, 1);
        }

        $im->writeImage($absolutePath);
        $im->destroy();

        $file->update(['preview_images' => [$relativePath], 'total_slides' => 1]);
        $this->info('  Generated 1 preview image.');

        return true;
    }

    /**
     * @return array{paths: string[], totalPages: int}
     */
    private function generateSlideImages(string $pdfPath, int $fileId, int $maxSlides): array
    {
        $this->info('  Generating slide images from PDF...');

        $previewDir = "file-previews/{$fileId}";
        Storage::disk('public')->makeDirectory($previewDir);

        $paths = [];

        $imagick = new Imagick;
        $imagick->setResolution(200, 200);

        $imagick->pingImage($pdfPath);
        $totalPages = $imagick->getNumberImages();
        $pagesToProcess = min($totalPages, $maxSlides);

        $this->info("  Found {$totalPages} pages, processing {$pagesToProcess}...");

        for ($i = 0; $i < $pagesToProcess; $i++) {
            $im = new Imagick;
            $im->setResolution(200, 200);
            $im->readImage("{$pdfPath}[{$i}]");
            $im->setImageBackgroundColor('white');
            $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
            $im = $im->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $im->setImageFormat('jpeg');
            $im->setImageCompressionQuality(85);

            $width = $im->getImageWidth();

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

        return ['paths' => $paths, 'totalPages' => $totalPages];
    }
}
