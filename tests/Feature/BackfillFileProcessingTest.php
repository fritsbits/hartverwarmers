<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\File;
use App\Models\Initiative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillFileProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_links_pdf_twins_by_matching_base_filename(): void
    {
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $pptx = File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'presentation.pptx',
            'path' => 'files/test/presentation.pptx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'size_bytes' => 1000,
            'sort_order' => 0,
        ]);

        $pdf = File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'presentation.pdf',
            'path' => 'files/test/presentation.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 500,
            'sort_order' => 1,
        ]);

        $this->artisan('app:backfill-files', ['--link-twins-only' => true])
            ->assertSuccessful();

        $pdf->refresh();
        $this->assertEquals($pptx->id, $pdf->source_file_id);
    }

    public function test_does_not_link_pdfs_with_different_base_filename(): void
    {
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $pptx = File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'slides.pptx',
            'path' => 'files/test/slides.pptx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'size_bytes' => 1000,
            'sort_order' => 0,
        ]);

        $pdf = File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'handout.pdf',
            'path' => 'files/test/handout.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 500,
            'sort_order' => 1,
        ]);

        $this->artisan('app:backfill-files', ['--link-twins-only' => true])
            ->assertSuccessful();

        $pdf->refresh();
        $this->assertNull($pdf->source_file_id);
    }
}
