<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\File;
use App\Models\Initiative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FicheDownloadTest extends TestCase
{
    use RefreshDatabase;

    private Initiative $initiative;

    private Fiche $fiche;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->initiative = Initiative::factory()->published()->create();
        $this->fiche = Fiche::factory()->published()->create([
            'initiative_id' => $this->initiative->id,
            'download_count' => 0,
        ]);
    }

    public function test_download_single_file_returns_file_response(): void
    {
        $file = File::factory()->create([
            'fiche_id' => $this->fiche->id,
            'original_filename' => 'activiteit.pdf',
            'path' => 'files/test-file.pdf',
            'mime_type' => 'application/pdf',
        ]);
        Storage::disk('public')->put($file->path, 'fake pdf content');

        $response = $this->get(route('fiches.download', [$this->initiative, $this->fiche]));

        $response->assertStatus(200);
        $response->assertDownload('activiteit.pdf');
    }

    public function test_download_multiple_files_returns_zip(): void
    {
        $files = collect([
            ['original_filename' => 'presentatie.pptx', 'path' => 'files/a.pptx', 'mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            ['original_filename' => 'bijlage.pdf', 'path' => 'files/b.pdf', 'mime_type' => 'application/pdf'],
            ['original_filename' => 'extra.pdf', 'path' => 'files/c.pdf', 'mime_type' => 'application/pdf'],
        ]);

        foreach ($files as $fileData) {
            File::factory()->create(array_merge($fileData, ['fiche_id' => $this->fiche->id]));
            Storage::disk('public')->put($fileData['path'], 'fake content');
        }

        $response = $this->get(route('fiches.download', [$this->initiative, $this->fiche]));

        $response->assertStatus(200);
        $response->assertDownload($this->fiche->slug.'-bestanden.zip');
    }

    public function test_download_increments_download_count(): void
    {
        $file = File::factory()->create([
            'fiche_id' => $this->fiche->id,
            'path' => 'files/test.pdf',
        ]);
        Storage::disk('public')->put($file->path, 'fake content');

        $this->assertEquals(0, $this->fiche->download_count);

        $this->get(route('fiches.download', [$this->initiative, $this->fiche]));

        $this->assertEquals(1, $this->fiche->fresh()->download_count);
    }

    public function test_download_404_for_unpublished_fiche(): void
    {
        $unpublishedFiche = Fiche::factory()->create([
            'initiative_id' => $this->initiative->id,
            'published' => false,
        ]);
        File::factory()->create(['fiche_id' => $unpublishedFiche->id]);

        $response = $this->get(route('fiches.download', [$this->initiative, $unpublishedFiche]));

        $response->assertStatus(404);
    }

    public function test_download_404_for_fiche_without_files(): void
    {
        $response = $this->get(route('fiches.download', [$this->initiative, $this->fiche]));

        $response->assertStatus(404);
    }

    public function test_show_page_displays_download_section_with_single_file(): void
    {
        File::factory()->create([
            'fiche_id' => $this->fiche->id,
            'original_filename' => 'rapport.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2_500_000,
        ]);

        $response = $this->get(route('fiches.show', [$this->initiative, $this->fiche]));

        $response->assertStatus(200);
        $response->assertSee('Download');
        $response->assertSee('2 MB');
        $response->assertSee(route('fiches.download', [$this->initiative, $this->fiche]));
    }

    public function test_download_includes_generated_pdf_versions(): void
    {
        $sourceFile = File::factory()->pptx()->create([
            'fiche_id' => $this->fiche->id,
            'original_filename' => 'presentatie.pptx',
            'path' => 'files/a.pptx',
        ]);
        Storage::disk('public')->put($sourceFile->path, 'fake pptx');

        $pdfFile = File::factory()->generatedPdf($sourceFile)->create([
            'original_filename' => 'presentatie.pdf',
            'path' => 'files/a.pdf',
        ]);
        Storage::disk('public')->put($pdfFile->path, 'fake pdf');

        $response = $this->get(route('fiches.download', [$this->initiative, $this->fiche]));

        $response->assertStatus(200);
        $response->assertDownload($this->fiche->slug.'-bestanden.zip');
    }

    public function test_show_page_displays_uploaded_file_count_excluding_generated(): void
    {
        $sourceFile = File::factory()->pptx()->create([
            'fiche_id' => $this->fiche->id,
            'original_filename' => 'presentatie.pptx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'size_bytes' => 1_000_000,
        ]);
        File::factory()->generatedPdf($sourceFile)->create([
            'size_bytes' => 500_000,
        ]);

        $response = $this->get(route('fiches.show', [$this->initiative, $this->fiche]));

        $response->assertStatus(200);
        $response->assertSee('Download bestand');
        $response->assertSee('(incl. PDF)');
    }

    public function test_show_page_displays_type_pills_for_multiple_files(): void
    {
        File::factory()->create([
            'fiche_id' => $this->fiche->id,
            'original_filename' => 'presentatie.pptx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'size_bytes' => 1_000_000,
        ]);
        File::factory()->create([
            'fiche_id' => $this->fiche->id,
            'original_filename' => 'bijlage1.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 500_000,
        ]);
        File::factory()->create([
            'fiche_id' => $this->fiche->id,
            'original_filename' => 'bijlage2.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 500_000,
        ]);

        $response = $this->get(route('fiches.show', [$this->initiative, $this->fiche]));

        $response->assertStatus(200);
        $response->assertSee('3 bestanden');
        $response->assertSee('Download');
    }
}
