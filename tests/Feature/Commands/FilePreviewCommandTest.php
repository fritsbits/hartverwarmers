<?php

namespace Tests\Feature\Commands;

use App\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FilePreviewCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
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

    public function test_command_skips_unsupported_mime_types(): void
    {
        $file = File::factory()->create([
            'mime_type' => 'application/zip',
            'original_filename' => 'archive.zip',
        ]);
        Storage::disk('public')->put($file->path, 'fake content');

        $this->artisan('file:generate-previews', ['--file' => $file->id])
            ->expectsOutputToContain('Skipping: unsupported file type')
            ->assertExitCode(1);

        $this->assertNull($file->fresh()->preview_images);
    }

    public function test_all_flag_processes_files_without_previews(): void
    {
        $noPreview = File::factory()->create([
            'preview_images' => null,
            'mime_type' => 'application/zip',
        ]);
        Storage::disk('public')->put($noPreview->path, 'fake content');

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
        Storage::disk('public')->put($file->path, 'fake content');

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
            ->expectsOutputToContain('File not found on disk')
            ->assertExitCode(1);
    }
}
