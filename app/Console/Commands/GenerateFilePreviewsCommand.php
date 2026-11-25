<?php

namespace App\Console\Commands;

use App\Concerns\ConvertsDocumentsViaSoffice;
use App\Models\File;
use App\Services\FicheStorage\FicheStorage;
use App\Services\FicheStorage\ObjectMissing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Imagick;
use Throwable;

class GenerateFilePreviewsCommand extends Command
{
    use ConvertsDocumentsViaSoffice;

    protected $signature = 'file:generate-previews
        {--file= : Specific file ID}
        {--all : Process all files without previews}
        {--max-slides=8 : Maximum number of slide previews to generate}
        {--thumbnails-only : Generate thumbnails for existing preview images}';

    protected $description = 'Generate preview images for uploaded files (PPTX, DOCX, PDF, images)';

    public function __construct(private FicheStorage $storage)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->ensureGhostscriptInPath();

        if ($this->option('thumbnails-only')) {
            return $this->backfillThumbnails();
        }

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

    /**
     * Ensure Ghostscript (gs) is reachable by ImageMagick.
     *
     * Queue workers often run with a minimal PATH that excludes
     * Homebrew's /opt/homebrew/bin, causing Imagick PDF rendering to fail.
     */
    private function ensureGhostscriptInPath(): void
    {
        $extraPaths = ['/opt/homebrew/bin', '/usr/local/bin'];
        $currentPath = getenv('PATH') ?: '';

        $missing = array_filter($extraPaths, fn (string $p) => ! str_contains($currentPath, $p) && is_dir($p));

        if (! empty($missing)) {
            putenv('PATH='.implode(':', $missing).':'.$currentPath);
        }
    }

    /**
     * Render previews in a private workspace, upload every image and
     * thumbnail, and only then publish the list on the file record. A
     * failure leaves the previous previews in place and returns false, so
     * the queued job retries.
     */
    private function processFile(File $file, int $maxSlides): bool
    {
        $this->info("Processing: {$file->original_filename} (ID: {$file->id})");

        $isPptx = str_contains($file->mime_type, 'presentation') || str_contains($file->mime_type, 'powerpoint');
        $isDocx = str_contains($file->mime_type, 'word') || str_contains($file->mime_type, 'document');
        $isPdf = str_contains($file->mime_type, 'pdf');
        $isImage = str_starts_with($file->mime_type, 'image/');

        if (! $isPptx && ! $isDocx && ! $isPdf && ! $isImage) {
            $this->warn("  Skipping: unsupported file type ({$file->mime_type})");

            return true;
        }

        try {
            return Cache::lock("fiche-file:{$file->id}:previews", 600)->block(60, fn () => $this->storage->withWorkspace(function (string $workspace) use ($file, $maxSlides, $isImage, $isPptx, $isDocx) {
                $result = $isImage
                    ? $this->storage->withLocalCopy(FicheStorage::FILES, $file->path, fn (string $source) => $this->renderImage($source, $workspace, $file->id))
                    : $this->renderDocument($file, $workspace, $maxSlides, $isPptx || $isDocx);

                if ($result === null || $result['paths'] === []) {
                    $this->error('  No preview images generated.');

                    return false;
                }

                foreach ($result['outputs'] as $relativePath => $localPath) {
                    $this->storage->putFile(FicheStorage::PREVIEWS, $relativePath, $localPath);
                }

                $file->update([
                    'preview_images' => $result['paths'],
                    'total_slides' => $result['totalPages'],
                ]);
                $this->info('  Generated '.count($result['paths']).' preview images (total: '.$result['totalPages'].' pages).');

                return true;
            }));
        } catch (ObjectMissing) {
            $this->error("  File not found in storage: {$file->path}");

            return false;
        } catch (Throwable $e) {
            $this->error("  Failed: {$e->getMessage()}");
            report($e);

            return false;
        }
    }

    /**
     * @return array{paths: array<int, string>, totalPages: int, outputs: array<string, string>}|null
     */
    private function renderDocument(File $file, string $workspace, int $maxSlides, bool $needsPdf): ?array
    {
        $existingPdf = $needsPdf ? File::where('source_file_id', $file->id)->first() : null;

        if ($existingPdf) {
            $this->info('  Reusing existing PDF version.');
        }

        $source = $existingPdf ? $existingPdf->path : $file->path;

        return $this->storage->withLocalCopy(FicheStorage::FILES, $source, function (string $sourcePath) use ($file, $workspace, $maxSlides, $needsPdf, $existingPdf) {
            $pdfPath = $needsPdf && ! $existingPdf ? $this->convertToPdf($sourcePath, $workspace) : $sourcePath;

            if (! $pdfPath) {
                return null;
            }

            return $this->renderSlides($pdfPath, $workspace, $file->id, $maxSlides);
        });
    }

    /**
     * @return array{paths: array<int, string>, totalPages: int, outputs: array<string, string>}
     */
    private function renderImage(string $sourcePath, string $workspace, int $fileId): array
    {
        $this->info('  Processing image...');

        $relativePath = "file-previews/{$fileId}/slide-001.jpg";
        $localPath = $workspace.'/slide-001.jpg';

        $im = new Imagick($sourcePath);
        $im->setImageFormat('jpeg');
        $im->setImageCompressionQuality(85);

        if ($im->getImageWidth() > 1280) {
            $im->resizeImage(1280, 0, Imagick::FILTER_LANCZOS, 1);
        }

        $im->writeImage($localPath);
        $im->destroy();

        return [
            'paths' => [$relativePath],
            'totalPages' => 1,
            'outputs' => [
                $relativePath => $localPath,
                $this->thumbnailPath($relativePath) => $this->generateThumbnail($localPath, $this->thumbnailPath($localPath)),
            ],
        ];
    }

    /**
     * @return array{paths: array<int, string>, totalPages: int, outputs: array<string, string>}
     */
    private function renderSlides(string $pdfPath, string $workspace, int $fileId, int $maxSlides): array
    {
        $this->info('  Generating slide images from PDF...');

        $paths = [];
        $outputs = [];

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

            if ($im->getImageWidth() > 1280) {
                $im->resizeImage(1280, 0, Imagick::FILTER_LANCZOS, 1);
            }

            $slideNum = str_pad($i + 1, 3, '0', STR_PAD_LEFT);
            $relativePath = "file-previews/{$fileId}/slide-{$slideNum}.jpg";
            $localPath = "{$workspace}/slide-{$slideNum}.jpg";

            $im->writeImage($localPath);
            $im->destroy();

            $outputs[$relativePath] = $localPath;
            $outputs[$this->thumbnailPath($relativePath)] = $this->generateThumbnail($localPath, $this->thumbnailPath($localPath));
            $paths[] = $relativePath;
        }

        return ['paths' => $paths, 'totalPages' => $totalPages, 'outputs' => $outputs];
    }

    private function generateThumbnail(string $fullSizePath, string $thumbPath): string
    {
        $im = new Imagick($fullSizePath);
        $im->resizeImage(400, 0, Imagick::FILTER_LANCZOS, 1);
        $im->setImageCompressionQuality(80);
        $im->writeImage($thumbPath);
        $im->destroy();

        return $thumbPath;
    }

    private function thumbnailPath(string $path): string
    {
        return Str::replaceLast('.jpg', '-thumb.jpg', $path);
    }

    private function backfillThumbnails(): int
    {
        $files = File::whereNotNull('preview_images')->get();
        $this->info("Checking {$files->count()} files for missing thumbnails...");

        $generated = 0;
        $failed = 0;

        foreach ($files as $file) {
            foreach ($file->preview_images as $path) {
                $thumbPath = $this->thumbnailPath($path);

                try {
                    if ($this->storage->exists(FicheStorage::PREVIEWS, $thumbPath)) {
                        continue;
                    }

                    $this->storage->withLocalCopy(FicheStorage::PREVIEWS, $path, fn (string $fullPath) => $this->storage->withWorkspace(
                        fn (string $workspace) => $this->storage->putFile(
                            FicheStorage::PREVIEWS,
                            $thumbPath,
                            $this->generateThumbnail($fullPath, $workspace.'/thumb.jpg'),
                        ),
                    ));
                    $generated++;
                } catch (ObjectMissing) {
                    $this->warn("  Full-size missing: {$path}");
                } catch (Throwable $e) {
                    $this->error("  Failed for {$path}: {$e->getMessage()}");
                    report($e);
                    $failed++;
                }
            }
        }

        $this->info("Generated {$generated} thumbnails.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
