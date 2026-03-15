<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Initiative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportFilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS media (
            id BIGINT UNSIGNED PRIMARY KEY,
            model_type VARCHAR(255),
            model_id BIGINT UNSIGNED,
            collection_name VARCHAR(255),
            name VARCHAR(255),
            file_name VARCHAR(255),
            mime_type VARCHAR(255),
            disk VARCHAR(255),
            size BIGINT UNSIGNED,
            order_column INT UNSIGNED,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )');
    }

    public function test_imports_file_record_for_existing_file(): void
    {
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create([
            'initiative_id' => $initiative->id,
            'migration_id' => 11000,
        ]);

        DB::connection('soulcenter')->table('media')->insert([
            'id' => 5140,
            'model_type' => 'App\\Models\\Activity',
            'model_id' => 11000,
            'collection_name' => 'downloads',
            'name' => 'algemene-quiz',
            'file_name' => 'algemene-quiz.pptx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'disk' => 'media',
            'size' => 44585940,
            'order_column' => 1,
            'created_at' => now(),
        ]);

        // Create the file on disk
        Storage::disk('public')->makeDirectory('files/media/5140');
        Storage::disk('public')->put('files/media/5140/algemene-quiz.pptx', 'fake content');

        $this->artisan('app:import-files')->assertSuccessful();

        $this->assertDatabaseHas('files', [
            'fiche_id' => $fiche->id,
            'original_filename' => 'algemene-quiz.pptx',
            'path' => 'files/media/5140/algemene-quiz.pptx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'size_bytes' => 44585940,
        ]);
    }

    public function test_skips_file_not_on_disk(): void
    {
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create([
            'initiative_id' => $initiative->id,
            'migration_id' => 12000,
        ]);

        DB::connection('soulcenter')->table('media')->insert([
            'id' => 9999,
            'model_type' => 'App\\Models\\Activity',
            'model_id' => 12000,
            'collection_name' => 'downloads',
            'name' => 'missing-file',
            'file_name' => 'missing-file.pdf',
            'mime_type' => 'application/pdf',
            'disk' => 'media',
            'size' => 1000,
            'order_column' => 1,
            'created_at' => now(),
        ]);

        // Do NOT create the file on disk

        $this->artisan('app:import-files')->assertSuccessful();

        $this->assertDatabaseCount('files', 0);
    }
}
