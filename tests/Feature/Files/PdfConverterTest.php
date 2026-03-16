<?php

namespace Tests\Feature\Files;

use App\Models\Fiche;
use App\Models\File;
use App\Models\Initiative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfConverterTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_model_relationships(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $sourceFile = File::factory()->pptx()->create(['fiche_id' => $fiche->id]);
        $pdfFile = File::factory()->generatedPdf($sourceFile)->create();

        $this->assertTrue($sourceFile->isConvertibleToPdf());
        $this->assertFalse($pdfFile->isConvertibleToPdf());
        $this->assertTrue($pdfFile->isGenerated());
        $this->assertFalse($sourceFile->isGenerated());
        $this->assertEquals($pdfFile->id, $sourceFile->pdfVersion->id);
        $this->assertEquals($sourceFile->id, $pdfFile->sourceFile->id);
    }

    public function test_pdf_file_is_not_convertible(): void
    {
        $file = File::factory()->make(['mime_type' => 'application/pdf']);

        $this->assertFalse($file->isConvertibleToPdf());
    }

    public function test_image_file_is_not_convertible(): void
    {
        $file = File::factory()->make(['mime_type' => 'image/jpeg']);

        $this->assertFalse($file->isConvertibleToPdf());
    }

    public function test_docx_file_is_convertible(): void
    {
        $file = File::factory()->docx()->make();

        $this->assertTrue($file->isConvertibleToPdf());
    }

    public function test_pptx_file_is_convertible(): void
    {
        $file = File::factory()->pptx()->make();

        $this->assertTrue($file->isConvertibleToPdf());
    }

    public function test_generated_pdf_included_in_fiche_files(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $sourceFile = File::factory()->pptx()->create(['fiche_id' => $fiche->id]);
        File::factory()->generatedPdf($sourceFile)->create();

        $this->assertEquals(2, $fiche->files()->count());
    }

    public function test_null_on_delete_preserves_pdf_when_source_deleted(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $sourceFile = File::factory()->pptx()->create(['fiche_id' => $fiche->id]);
        $pdfFile = File::factory()->generatedPdf($sourceFile)->create();

        $sourceFile->delete();

        $pdfFile->refresh();
        $this->assertNull($pdfFile->source_file_id);
        $this->assertDatabaseHas('files', ['id' => $pdfFile->id]);
    }
}
