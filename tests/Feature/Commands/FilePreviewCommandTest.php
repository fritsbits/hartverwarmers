<?php

namespace Tests\Feature\Commands;

use App\Jobs\GenerateFilePreview;
use App\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\FakesFicheStorage;
use Tests\TestCase;

class FilePreviewCommandTest extends TestCase
{
    use FakesFicheStorage, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFicheStorage();
    }

    public function test_command_requires_file_or_all_flag(): void
    {
        $this->artisan('file:generate-previews')
            ->expectsOutput('Please specify --file=ID or --all')
            ->assertExitCode(1);
    }

    public function test_command_fails_for_nonexistent_file(): void
    {
        $this->artisan('file:generate-previews', ['--file' => 999])
            ->expectsOutput('File not found.')
            ->assertExitCode(1);
    }

    public function test_command_skips_unsupported_mime_types_without_failing(): void
    {
        $file = File::factory()->create([
            'mime_type' => 'application/zip',
            'original_filename' => 'archive.zip',
        ]);
        $this->localFiles()->put($file->path, 'fake content');

        $this->artisan('file:generate-previews', ['--file' => $file->id])
            ->expectsOutputToContain('Skipping: unsupported file type')
            ->assertExitCode(0);

        $this->assertNull($file->fresh()->preview_images);
    }

    public function test_all_flag_processes_files_without_previews(): void
    {
        $noPreview = File::factory()->create([
            'preview_images' => null,
            'mime_type' => 'application/zip',
        ]);
        $this->localFiles()->put($noPreview->path, 'fake content');

        $hasPreview = File::factory()->withPreviews()->create();

        $this->artisan('file:generate-previews', ['--all' => true])
            ->expectsOutputToContain('Processing 1 files')
            ->assertExitCode(0);

        $hasPreview->refresh();
        $this->assertNotNull($hasPreview->preview_images);
    }

    public function test_all_flag_includes_files_with_null_total_slides(): void
    {
        $file = File::factory()->create([
            'preview_images' => ['file-previews/1/slide-001.jpg'],
            'total_slides' => null,
            'mime_type' => 'application/zip',
        ]);
        $this->localFiles()->put($file->path, 'fake content');

        $this->artisan('file:generate-previews', ['--all' => true])
            ->expectsOutputToContain('Processing 1 files')
            ->assertExitCode(0);
    }

    public function test_command_fails_when_file_not_on_disk(): void
    {
        $file = File::factory()->create([
            'mime_type' => 'application/pdf',
        ]);
        // Don't put file on disk

        $this->artisan('file:generate-previews', ['--file' => $file->id])
            ->expectsOutputToContain('File not found in storage')
            ->assertExitCode(1);
    }

    public function test_job_throws_so_the_queue_retries_when_the_command_fails(): void
    {
        $file = File::factory()->create(['mime_type' => 'application/pdf']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Preview generation failed for file {$file->id}");

        (new GenerateFilePreview($file->id))->handle();
    }

    public function test_job_does_not_throw_for_an_unsupported_mime_type(): void
    {
        $file = File::factory()->create(['mime_type' => 'application/zip']);
        $this->localFiles()->put($file->path, 'zip');

        (new GenerateFilePreview($file->id))->handle();

        $this->assertNull($file->fresh()->preview_images);
    }

    public function test_mirror_mode_writes_slides_and_thumbnails_to_both_preview_disks(): void
    {
        $this->fakeFicheStorage('mirror');
        $file = File::factory()->create(['mime_type' => 'application/pdf']);
        $this->localFiles()->put($file->path, file_get_contents(base_path('tests/fixtures/sample.pdf')));

        $this->artisan('file:generate-previews', ['--file' => $file->id])->assertSuccessful();

        $file->refresh();
        $this->assertNotEmpty($file->preview_images);
        $this->assertNotNull($file->total_slides);

        foreach ([...$file->preview_images, ...$file->thumbnailPaths()] as $path) {
            $this->localPreviews()->assertExists($path);
            $this->remotePreviews()->assertExists($path);
        }
        $this->assertStringEndsWith('slide-001-thumb.jpg', $file->thumbnailPaths()[0]);
    }

    public function test_failing_remote_preview_upload_fails_the_command_and_publishes_nothing(): void
    {
        $this->fakeFicheStorage('mirror');
        $this->breakRemote('fiche-previews-s3', 'writeStream');
        $file = File::factory()->create(['mime_type' => 'application/pdf']);
        $this->localFiles()->put($file->path, file_get_contents(base_path('tests/fixtures/sample.pdf')));

        $this->artisan('file:generate-previews', ['--file' => $file->id])->assertExitCode(1);

        $file->refresh();
        $this->assertNull($file->preview_images);
        $this->assertNull($file->total_slides);
    }
}
