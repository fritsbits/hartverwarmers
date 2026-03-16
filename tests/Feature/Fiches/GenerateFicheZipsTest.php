<?php

namespace Tests\Feature\Fiches;

use App\Models\Fiche;
use App\Models\File;
use App\Models\Initiative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateFicheZipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_zip_for_fiche_with_multiple_files(): void
    {
        Storage::fake('public');

        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Storage::disk('public')->put('files/a.pdf', 'content-a');
        Storage::disk('public')->put('files/b.pdf', 'content-b');

        File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'document-a.pdf',
            'path' => 'files/a.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 9,
            'sort_order' => 0,
        ]);
        File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'document-b.pdf',
            'path' => 'files/b.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 9,
            'sort_order' => 1,
        ]);

        $this->artisan('app:generate-zips')->assertSuccessful();

        $fiche->refresh();
        $this->assertNotNull($fiche->zip_path);
        $this->assertTrue(Storage::disk('public')->exists($fiche->zip_path));
    }

    public function test_skips_fiche_with_single_file(): void
    {
        Storage::fake('public');

        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Storage::disk('public')->put('files/only.pdf', 'content');

        File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'only.pdf',
            'path' => 'files/only.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 7,
            'sort_order' => 0,
        ]);

        $this->artisan('app:generate-zips')->assertSuccessful();

        $fiche->refresh();
        $this->assertNull($fiche->zip_path);
    }

    public function test_excludes_generated_pdfs_from_zip(): void
    {
        Storage::fake('public');

        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Storage::disk('public')->put('files/slides.pptx', 'pptx-content');
        Storage::disk('public')->put('files/slides.pdf', 'pdf-content');

        $pptx = File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'slides.pptx',
            'path' => 'files/slides.pptx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'size_bytes' => 12,
            'sort_order' => 0,
        ]);
        // Generated PDF — should NOT be included in ZIP
        File::create([
            'fiche_id' => $fiche->id,
            'source_file_id' => $pptx->id,
            'original_filename' => 'slides.pdf',
            'path' => 'files/slides.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 11,
            'sort_order' => 1,
        ]);

        $this->artisan('app:generate-zips')->assertSuccessful();

        $fiche->refresh();
        // Only 1 original file → no ZIP needed
        $this->assertNull($fiche->zip_path);
    }

    public function test_regenerates_zip_when_force_flag_used(): void
    {
        Storage::fake('public');

        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'zip_path' => 'fiche-zips/old.zip',
        ]);

        Storage::disk('public')->put('files/a.pdf', 'content-a');
        Storage::disk('public')->put('files/b.pdf', 'content-b');
        Storage::disk('public')->put('fiche-zips/old.zip', 'old-zip');

        File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'a.pdf',
            'path' => 'files/a.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 9,
            'sort_order' => 0,
        ]);
        File::create([
            'fiche_id' => $fiche->id,
            'original_filename' => 'b.pdf',
            'path' => 'files/b.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 9,
            'sort_order' => 1,
        ]);

        $this->artisan('app:generate-zips', ['--force' => true])->assertSuccessful();

        $fiche->refresh();
        $this->assertNotEquals('fiche-zips/old.zip', $fiche->zip_path);
        $this->assertTrue(Storage::disk('public')->exists($fiche->zip_path));
    }
}
