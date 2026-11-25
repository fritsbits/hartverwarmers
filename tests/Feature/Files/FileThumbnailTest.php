<?php

namespace Tests\Feature\Files;

use App\Models\Fiche;
use App\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesFicheStorage;
use Tests\TestCase;

class FileThumbnailTest extends TestCase
{
    use FakesFicheStorage, RefreshDatabase;

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
        $this->fakeFicheStorage();

        $fiche = Fiche::factory()->published()->create();
        $previewPaths = ['file-previews/1/slide-001.jpg', 'file-previews/1/slide-002.jpg'];

        File::factory()->for($fiche)->create([
            'preview_images' => $previewPaths,
            'total_slides' => 2,
        ]);

        foreach ($previewPaths as $path) {
            $this->localFiles()->put($path, 'fake-image-content');
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
        $this->fakeFicheStorage();

        $fiche = Fiche::factory()->published()->create();
        $previewPaths = ['file-previews/1/slide-001.jpg', 'file-previews/1/slide-002.jpg'];
        $thumbPaths = ['file-previews/1/slide-001-thumb.jpg', 'file-previews/1/slide-002-thumb.jpg'];

        File::factory()->for($fiche)->create([
            'preview_images' => $previewPaths,
            'total_slides' => 2,
        ]);

        foreach ($previewPaths as $path) {
            $this->localFiles()->put($path, 'fake-full-size-content');
        }
        foreach ($thumbPaths as $path) {
            $this->localFiles()->put($path, 'fake-thumb-content');
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
        $this->fakeFicheStorage();

        $file = File::factory()->create([
            'preview_images' => ['file-previews/99/slide-001.jpg'],
            'total_slides' => 1,
        ]);

        $fullPath = 'file-previews/99/slide-001.jpg';
        $thumbPath = 'file-previews/99/slide-001-thumb.jpg';

        $im = new \Imagick;
        $im->newImage(800, 600, 'white');
        $im->setImageFormat('jpeg');
        $this->localFiles()->makeDirectory('file-previews/99');
        $absolutePath = $this->localFiles()->path($fullPath);
        $im->writeImage($absolutePath);
        $im->destroy();

        $this->assertFalse(file_exists($this->localFiles()->path($thumbPath)));

        $this->artisan('file:generate-previews', ['--thumbnails-only' => true])
            ->assertSuccessful();

        $this->assertTrue(file_exists($this->localFiles()->path($thumbPath)));

        $thumbIm = new \Imagick($this->localFiles()->path($thumbPath));
        $this->assertEquals(400, $thumbIm->getImageWidth());
        $thumbIm->destroy();
    }

    public function test_s3_thumbnail_backfill_reads_remote_full_size_and_writes_remote_only(): void
    {
        $this->fakeFicheStorage('s3');

        $file = File::factory()->create([
            'preview_images' => ['file-previews/77/slide-001.jpg'],
            'total_slides' => 1,
        ]);

        $workspace = sys_get_temp_dir().'/thumb-src-'.uniqid().'.jpg';
        $im = new \Imagick;
        $im->newImage(800, 600, 'white');
        $im->setImageFormat('jpeg');
        $im->writeImage($workspace);
        $im->destroy();
        $this->remotePreviews()->put('file-previews/77/slide-001.jpg', file_get_contents($workspace));
        @unlink($workspace);

        $this->artisan('file:generate-previews', ['--thumbnails-only' => true])->assertSuccessful();

        $thumb = 'file-previews/77/slide-001-thumb.jpg';
        $this->remotePreviews()->assertExists($thumb);
        $this->localPreviews()->assertMissing($thumb);

        $generated = new \Imagick;
        $generated->readImageBlob($this->remotePreviews()->get($thumb));
        $this->assertSame(400, $generated->getImageWidth());
        $generated->destroy();
    }
}
