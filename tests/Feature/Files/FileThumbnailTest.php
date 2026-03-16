<?php

namespace Tests\Feature\Files;

use App\Models\Fiche;
use App\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileThumbnailTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_thumbnail_paths_derives_thumb_suffix(): void
    {
        $file = File::factory()->withPreviews(2)->create();

        $thumbPaths = $file->thumbnailPaths();

        $this->assertCount(2, $thumbPaths);
        foreach ($thumbPaths as $path) {
            $this->assertStringEndsWith('-thumb.jpg', $path);
        }

        foreach ($file->preview_images as $index => $original) {
            $expected = str_replace('.jpg', '-thumb.jpg', $original);
            $this->assertEquals($expected, $thumbPaths[$index]);
        }
    }

    public function test_file_thumbnail_paths_empty_when_no_previews(): void
    {
        $file = File::factory()->create();

        $this->assertEmpty($file->thumbnailPaths());
    }

    public function test_card_preview_images_falls_back_to_full_size(): void
    {
        Storage::fake('public');

        $fiche = Fiche::factory()->published()->create();
        $previewPaths = ['file-previews/1/slide-001.jpg', 'file-previews/1/slide-002.jpg'];

        File::factory()->for($fiche)->create([
            'preview_images' => $previewPaths,
            'total_slides' => 2,
        ]);

        foreach ($previewPaths as $path) {
            Storage::disk('public')->put($path, 'fake-image-content');
        }

        $fiche->load('files');
        $urls = $fiche->cardPreviewImages();

        $this->assertCount(2, $urls);
        foreach ($urls as $index => $url) {
            $this->assertStringContainsString($previewPaths[$index], $url);
            $this->assertStringNotContainsString('-thumb', $url);
        }
    }

    public function test_card_preview_images_prefers_thumbnails(): void
    {
        Storage::fake('public');

        $fiche = Fiche::factory()->published()->create();
        $previewPaths = ['file-previews/1/slide-001.jpg', 'file-previews/1/slide-002.jpg'];
        $thumbPaths = ['file-previews/1/slide-001-thumb.jpg', 'file-previews/1/slide-002-thumb.jpg'];

        File::factory()->for($fiche)->create([
            'preview_images' => $previewPaths,
            'total_slides' => 2,
        ]);

        foreach ($previewPaths as $path) {
            Storage::disk('public')->put($path, 'fake-full-size-content');
        }
        foreach ($thumbPaths as $path) {
            Storage::disk('public')->put($path, 'fake-thumb-content');
        }

        $fiche->load('files');
        $urls = $fiche->cardPreviewImages();

        $this->assertCount(2, $urls);
        foreach ($urls as $index => $url) {
            $this->assertStringContainsString($thumbPaths[$index], $url);
        }
    }

    public function test_generate_previews_thumbnails_only_flag(): void
    {
        Storage::fake('public');

        $file = File::factory()->create([
            'preview_images' => ['file-previews/99/slide-001.jpg'],
            'total_slides' => 1,
        ]);

        $fullPath = 'file-previews/99/slide-001.jpg';
        $thumbPath = 'file-previews/99/slide-001-thumb.jpg';

        $im = new \Imagick;
        $im->newImage(800, 600, 'white');
        $im->setImageFormat('jpeg');
        Storage::disk('public')->makeDirectory('file-previews/99');
        $absolutePath = Storage::disk('public')->path($fullPath);
        $im->writeImage($absolutePath);
        $im->destroy();

        $this->assertFalse(file_exists(Storage::disk('public')->path($thumbPath)));

        $this->artisan('file:generate-previews', ['--thumbnails-only' => true])
            ->assertSuccessful();

        $this->assertTrue(file_exists(Storage::disk('public')->path($thumbPath)));

        $thumbIm = new \Imagick(Storage::disk('public')->path($thumbPath));
        $this->assertEquals(400, $thumbIm->getImageWidth());
        $thumbIm->destroy();
    }
}
