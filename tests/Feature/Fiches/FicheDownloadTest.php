<?php

namespace Tests\Feature\Fiches;

use App\Models\Fiche;
use App\Models\File;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\FakesFicheStorage;
use Tests\TestCase;

class FicheDownloadTest extends TestCase
{
    use FakesFicheStorage, RefreshDatabase;

    private Initiative $initiative;

    private Fiche $fiche;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFicheStorage();

        $this->user = User::factory()->create();
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
        $this->localFiles()->put($file->path, 'fake pdf content');

        $response = $this->actingAs($this->user)->get(route('fiches.download', [$this->initiative, $this->fiche]));

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
            $this->localFiles()->put($fileData['path'], 'fake content');
        }

        $response = $this->actingAs($this->user)->get(route('fiches.download', [$this->initiative, $this->fiche]));

        $response->assertStatus(200);
        $response->assertDownload($this->fiche->slug.'-bestanden.zip');
    }

    public function test_download_increments_download_count(): void
    {
        $file = File::factory()->create([
            'fiche_id' => $this->fiche->id,
            'path' => 'files/test.pdf',
        ]);
        $this->localFiles()->put($file->path, 'fake content');

        $this->assertEquals(0, $this->fiche->download_count);

        $this->actingAs($this->user)->get(route('fiches.download', [$this->initiative, $this->fiche]));

        $this->assertEquals(1, $this->fiche->fresh()->download_count);
    }

    public function test_download_404_for_unpublished_fiche(): void
    {
        $unpublishedFiche = Fiche::factory()->create([
            'initiative_id' => $this->initiative->id,
            'published' => false,
        ]);
        File::factory()->create(['fiche_id' => $unpublishedFiche->id]);

        $response = $this->actingAs($this->user)->get(route('fiches.download', [$this->initiative, $unpublishedFiche]));

        $response->assertStatus(404);
    }

    public function test_download_404_for_fiche_without_files(): void
    {
        $response = $this->actingAs($this->user)->get(route('fiches.download', [$this->initiative, $this->fiche]));

        $response->assertStatus(404);
    }

    public function test_guest_download_redirects_to_login(): void
    {
        $file = File::factory()->create([
            'fiche_id' => $this->fiche->id,
            'path' => 'files/test.pdf',
        ]);
        $this->localFiles()->put($file->path, 'fake content');

        $response = $this->get(route('fiches.download', [$this->initiative, $this->fiche]));

        $response->assertRedirect(route('login'));
        $this->assertEquals(0, $this->fiche->fresh()->download_count);
    }

    public function test_show_page_displays_download_section_with_single_file_for_auth_user(): void
    {
        File::factory()->create([
            'fiche_id' => $this->fiche->id,
            'original_filename' => 'rapport.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2_500_000,
        ]);

        $response = $this->actingAs($this->user)->get(route('fiches.show', [$this->initiative, $this->fiche]));

        $response->assertStatus(200);
        $response->assertSee('Download');
        $response->assertSee('2 MB');
        $response->assertSee(route('fiches.download', [$this->initiative, $this->fiche]));
    }

    public function test_show_page_displays_auth_gate_cta_for_guest(): void
    {
        File::factory()->create([
            'fiche_id' => $this->fiche->id,
            'original_filename' => 'rapport.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2_500_000,
        ]);

        $response = $this->get(route('fiches.show', [$this->initiative, $this->fiche]));

        $response->assertStatus(200);
        $response->assertSee('Meld je aan om te downloaden');
        $response->assertSee(route('fiches.download.gate', [$this->initiative, $this->fiche]));
    }

    public function test_auth_gate_redirects_guest_to_register_with_intended_fiche_url(): void
    {
        $response = $this->get(route('fiches.download.gate', [$this->initiative, $this->fiche]));

        $response->assertRedirect(route('register'));
        $this->assertEquals(
            route('fiches.show', [$this->initiative, $this->fiche]),
            session('url.intended'),
        );
    }

    public function test_auth_gate_redirects_authenticated_user_directly_to_download(): void
    {
        $response = $this->actingAs($this->user)->get(route('fiches.download.gate', [$this->initiative, $this->fiche]));

        $response->assertRedirect(route('fiches.download', [$this->initiative, $this->fiche]));
    }

    public function test_auth_gate_404_for_unpublished_fiche(): void
    {
        $unpublishedFiche = Fiche::factory()->create([
            'initiative_id' => $this->initiative->id,
            'published' => false,
        ]);

        $response = $this->get(route('fiches.download.gate', [$this->initiative, $unpublishedFiche]));

        $response->assertStatus(404);
    }

    public function test_download_includes_generated_pdf_versions(): void
    {
        $sourceFile = File::factory()->pptx()->create([
            'fiche_id' => $this->fiche->id,
            'original_filename' => 'presentatie.pptx',
            'path' => 'files/a.pptx',
        ]);
        $this->localFiles()->put($sourceFile->path, 'fake pptx');

        $pdfFile = File::factory()->generatedPdf($sourceFile)->create([
            'original_filename' => 'presentatie.pdf',
            'path' => 'files/a.pdf',
        ]);
        $this->localFiles()->put($pdfFile->path, 'fake pdf');

        $response = $this->actingAs($this->user)->get(route('fiches.download', [$this->initiative, $this->fiche]));

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

        $response = $this->actingAs($this->user)->get(route('fiches.show', [$this->initiative, $this->fiche]));

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

        $response = $this->actingAs($this->user)->get(route('fiches.show', [$this->initiative, $this->fiche]));

        $response->assertStatus(200);
        $response->assertSee('3 bestanden');
        $response->assertSee('Download');
    }

    private function downloadUrl(): string
    {
        return route('fiches.download', [$this->initiative, $this->fiche]);
    }

    public function test_s3_single_remote_file_redirects_to_presigned_bucket_url(): void
    {
        $this->fakeFicheStorage('s3');
        $file = File::factory()->create([
            'fiche_id' => $this->fiche->id,
            'original_filename' => 'Muziek “bingo” café.pdf',
            'path' => 'files/remote.pdf',
            'mime_type' => 'application/pdf',
        ]);
        $this->remoteFiles()->put($file->path, 'remote pdf');

        $response = $this->actingAs($this->user)->get($this->downloadUrl());

        $response->assertStatus(302);
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://hartverwarmers-files.objects.test/files/remote.pdf?', $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('application/pdf', $query['ResponseContentType']);
        $this->assertStringStartsWith('attachment;', $query['ResponseContentDisposition']);
        $this->assertStringContainsString("filename*=utf-8''".rawurlencode('Muziek “bingo” café.pdf'), $query['ResponseContentDisposition']);
        $this->assertMatchesRegularExpression('/filename="[\x20-\x7E]+"/', $query['ResponseContentDisposition']);
        $this->assertSame(1, $this->fiche->fresh()->download_count);
    }

    public function test_local_mode_single_file_is_served_directly(): void
    {
        $file = File::factory()->create([
            'fiche_id' => $this->fiche->id,
            'original_filename' => 'lokaal.pdf',
            'path' => 'files/lokaal.pdf',
        ]);
        $this->localFiles()->put($file->path, 'local bytes');

        $this->actingAs($this->user)->get($this->downloadUrl())->assertOk()->assertDownload('lokaal.pdf');
    }

    /**
     * @return array<string, string> entry name => bytes
     */
    private function zipEntries(TestResponse $response): array
    {
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($path));

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[$zip->getNameIndex($i)] = $zip->getFromIndex($i);
        }
        $zip->close();
        @unlink($path);

        return $entries;
    }

    public function test_zip_contains_every_file_with_unique_names_for_duplicates(): void
    {
        foreach (['a' => 'eerste', 'b' => 'tweede'] as $key => $bytes) {
            $file = File::factory()->create([
                'fiche_id' => $this->fiche->id,
                'original_filename' => 'naam.pdf',
                'path' => "files/{$key}.pdf",
            ]);
            $this->localFiles()->put($file->path, $bytes);
        }

        $response = $this->actingAs($this->user)->get($this->downloadUrl());

        $response->assertOk()->assertDownload($this->fiche->slug.'-bestanden.zip');
        $entries = $this->zipEntries($response);

        $this->assertEqualsCanonicalizing(['naam.pdf', 'naam (2).pdf'], array_keys($entries));
        $this->assertEqualsCanonicalizing(['eerste', 'tweede'], array_values($entries));
    }

    public function test_zip_combines_local_and_remote_only_sources_in_s3_fallback_mode(): void
    {
        $this->fakeFicheStorage('s3-fallback');
        $local = File::factory()->create(['fiche_id' => $this->fiche->id, 'original_filename' => 'lokaal.pdf', 'path' => 'files/lokaal.pdf']);
        $remote = File::factory()->create(['fiche_id' => $this->fiche->id, 'original_filename' => 'remote.pdf', 'path' => 'files/remote.pdf']);
        $this->localFiles()->put($local->path, 'local bytes');
        $this->remoteFiles()->put($remote->path, 'remote bytes');

        $response = $this->actingAs($this->user)->get($this->downloadUrl());

        $response->assertOk();
        $this->assertSame(
            ['lokaal.pdf' => 'local bytes', 'remote.pdf' => 'remote bytes'],
            collect($this->zipEntries($response))->sortKeys()->all(),
        );
    }

    public function test_missing_source_file_redirects_back_without_counting_or_leaving_a_partial_zip(): void
    {
        $present = File::factory()->create(['fiche_id' => $this->fiche->id, 'path' => 'files/present.pdf']);
        File::factory()->create(['fiche_id' => $this->fiche->id, 'path' => 'files/gone.pdf']);
        $this->localFiles()->put($present->path, 'bytes');

        $zips = fn () => glob(config('fiche-storage.workspace.root').'/zip-*.zip') ?: [];
        $before = $zips();

        $response = $this->actingAs($this->user)->get($this->downloadUrl());

        $response->assertRedirect(route('fiches.show', [$this->initiative, $this->fiche]));
        $this->assertSame('danger', session('toast')['variant']);
        $this->assertSame(0, $this->fiche->fresh()->download_count);
        $this->assertSame($before, $zips());
    }

    public function test_missing_single_source_file_redirects_back_without_counting(): void
    {
        File::factory()->create(['fiche_id' => $this->fiche->id, 'path' => 'files/gone.pdf']);

        $response = $this->actingAs($this->user)->get($this->downloadUrl());

        $response->assertRedirect(route('fiches.show', [$this->initiative, $this->fiche]));
        $this->assertSame('danger', session('toast')['variant']);
        $this->assertSame(0, $this->fiche->fresh()->download_count);
    }

    public function test_download_asks_to_retry_when_all_zip_slots_are_taken(): void
    {
        config(['fiche-storage.downloads.max_concurrent_zips' => 1]);
        foreach (['a', 'b'] as $key) {
            $file = File::factory()->create(['fiche_id' => $this->fiche->id, 'path' => "files/{$key}.pdf"]);
            $this->localFiles()->put($file->path, 'bytes');
        }

        $lock = Cache::lock('fiche-zip-slot:0', 600);
        $this->assertTrue($lock->get());

        try {
            $response = $this->actingAs($this->user)->get($this->downloadUrl());
        } finally {
            $lock->release();
        }

        $response->assertRedirect(route('fiches.show', [$this->initiative, $this->fiche]));
        $this->assertSame('warning', session('toast')['variant']);
        $this->assertSame(0, $this->fiche->fresh()->download_count);
    }
}
