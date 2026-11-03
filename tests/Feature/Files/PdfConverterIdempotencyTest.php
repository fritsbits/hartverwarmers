<?php

namespace Tests\Feature\Files;

use App\Models\File;
use App\Services\PdfConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesFicheStorage;
use Tests\TestCase;

class PdfConverterIdempotencyTest extends TestCase
{
    use FakesFicheStorage, RefreshDatabase;

    public function test_convert_and_store_returns_the_existing_pdf_version_without_creating_another(): void
    {
        $this->fakeFicheStorage('mirror');
        $source = File::factory()->docx()->create();
        $existing = File::factory()->generatedPdf($source)->create();
        $count = File::count();

        $result = app(PdfConverter::class)->convertAndStore($source);

        $this->assertSame($existing->id, $result->id);
        $this->assertSame($count, File::count());
    }
}
