<?php

namespace Tests\Feature;

use App\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExtractFileTextCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_command_requires_file_or_all_flag(): void
    {
        $this->artisan('file:extract-text')
            ->expectsOutput('Please specify --file=ID or --all')
            ->assertExitCode(1);
    }

    public function test_command_fails_for_nonexistent_file(): void
    {
        $this->artisan('file:extract-text', ['--file' => 999])
            ->expectsOutput('File not found.')
            ->assertExitCode(1);
    }

    public function test_command_skips_images(): void
    {
        $file = File::factory()->create([
            'mime_type' => 'image/jpeg',
            'original_filename' => 'photo.jpg',
        ]);
        Storage::disk('public')->put($file->path, 'fake image content');

        $this->artisan('file:extract-text', ['--file' => $file->id])
            ->expectsOutputToContain('Skipping: images have no extractable text')
            ->assertExitCode(1);

        $this->assertNull($file->fresh()->extracted_text);
    }

    public function test_command_skips_unsupported_file_types(): void
    {
        $file = File::factory()->create([
            'mime_type' => 'application/zip',
            'original_filename' => 'archive.zip',
        ]);
        Storage::disk('public')->put($file->path, 'fake content');

        $this->artisan('file:extract-text', ['--file' => $file->id])
            ->expectsOutputToContain('Skipping: unsupported file type')
            ->assertExitCode(1);
    }

    public function test_command_extracts_text_from_pdf(): void
    {
        $file = File::factory()->create([
            'mime_type' => 'application/pdf',
            'original_filename' => 'test.pdf',
        ]);

        $pdfContent = file_get_contents(base_path('tests/fixtures/sample.pdf'));
        Storage::disk('public')->put($file->path, $pdfContent);

        $this->artisan('file:extract-text', ['--file' => $file->id])
            ->assertExitCode(0);

        $file->refresh();
        $this->assertNotNull($file->extracted_text);
        $this->assertStringContainsString('Hello PDF Test', $file->extracted_text);
    }

    public function test_command_extracts_text_from_pptx(): void
    {
        $file = File::factory()->pptx()->create([
            'original_filename' => 'test.pptx',
        ]);

        $pptxContent = file_get_contents(base_path('tests/fixtures/sample.pptx'));
        Storage::disk('public')->put($file->path, $pptxContent);

        $this->artisan('file:extract-text', ['--file' => $file->id])
            ->assertExitCode(0);

        $file->refresh();
        $this->assertNotNull($file->extracted_text);
        $this->assertStringContainsString('Hello PPTX Test', $file->extracted_text);
        $this->assertStringContainsString('Second line of text', $file->extracted_text);
        $this->assertStringContainsString('--- Slide 1 ---', $file->extracted_text);
    }

    public function test_command_extracts_text_from_docx(): void
    {
        $file = File::factory()->create([
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'original_filename' => 'test.docx',
            'path' => 'files/test.docx',
        ]);

        $docxContent = file_get_contents(base_path('tests/fixtures/sample.docx'));
        Storage::disk('public')->put($file->path, $docxContent);

        $this->artisan('file:extract-text', ['--file' => $file->id])
            ->assertExitCode(0);

        $file->refresh();
        $this->assertNotNull($file->extracted_text);
        $this->assertStringContainsString('Hello DOCX Test', $file->extracted_text);
        $this->assertStringContainsString('Second paragraph', $file->extracted_text);
    }

    public function test_all_flag_processes_files_without_text(): void
    {
        $pptxFile = File::factory()->pptx()->create([
            'extracted_text' => null,
        ]);
        $pptxContent = file_get_contents(base_path('tests/fixtures/sample.pptx'));
        Storage::disk('public')->put($pptxFile->path, $pptxContent);

        $alreadyProcessed = File::factory()->create([
            'extracted_text' => 'Already has text',
        ]);

        $this->artisan('file:extract-text', ['--all' => true])
            ->expectsOutputToContain('Processing 1 files')
            ->assertExitCode(0);

        $pptxFile->refresh();
        $this->assertNotNull($pptxFile->extracted_text);

        $alreadyProcessed->refresh();
        $this->assertEquals('Already has text', $alreadyProcessed->extracted_text);
    }
}
