<?php

namespace Tests\Feature\FicheStorage;

use App\Models\Fiche;
use App\Models\File;
use App\Services\FicheStorage\FicheStorage;
use App\Services\FicheStorage\MigrationRegister;
use App\Services\FicheStorage\ObjectMissing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToWriteFile;
use LogicException;
use Tests\Concerns\FakesFicheStorage;
use Tests\Concerns\RemoteFakeDisk;
use Tests\TestCase;

class FicheStorageTest extends TestCase
{
    use FakesFicheStorage, RefreshDatabase;

    private FicheStorage $storage;

    private MigrationRegister $register;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFicheStorage();
        $this->storage = app(FicheStorage::class);
        $this->register = app(MigrationRegister::class);
    }

    public function test_local_mode_writes_only_locally_and_leaves_the_register_alone(): void
    {
        $this->storage->putFile(FicheStorage::FILES, 'files/a.pdf', $this->tempFile('pdf-bytes'));

        $this->assertSame('pdf-bytes', $this->localFiles()->get('files/a.pdf'));
        $this->assertFalse($this->remoteFiles()->exists('files/a.pdf'));
        $this->assertNull($this->register->object(FicheStorage::FILES, 'files/a.pdf'));
    }

    public function test_mirror_mode_writes_both_copies_and_verifies_the_remote_bytes(): void
    {
        $this->useFicheStorageMode('mirror');
        File::factory()->create(['path' => 'files/a.pdf']);

        $this->storage->putFile(FicheStorage::FILES, 'files/a.pdf', $this->tempFile('pdf-bytes'));

        $this->assertSame('pdf-bytes', $this->localFiles()->get('files/a.pdf'));
        $this->assertSame('pdf-bytes', $this->remoteFiles()->get('files/a.pdf'));

        $object = $this->register->object(FicheStorage::FILES, 'files/a.pdf');
        $this->assertSame(MigrationRegister::STATE_VERIFIED, $object->state);
        $this->assertSame(hash('sha256', 'pdf-bytes'), $object->expected_sha256);
    }

    public function test_mirror_mode_fails_loudly_when_the_remote_write_fails(): void
    {
        $this->useFicheStorageMode('mirror');
        $this->replaceRemoteFilesDisk(new class(...$this->remoteDiskParts()) extends RemoteFakeDisk
        {
            public function writeStream($path, $resource, array $options = []): bool
            {
                throw UnableToWriteFile::atLocation($path, 'bucket offline');
            }
        });

        $this->expectException(UnableToWriteFile::class);

        $this->storage->putFile(FicheStorage::FILES, 'files/a.pdf', $this->tempFile('pdf-bytes'));
    }

    public function test_s3_mode_writes_only_remotely(): void
    {
        $this->useFicheStorageMode('s3');

        $this->storage->putFile(FicheStorage::FILES, 'files/a.pdf', $this->tempFile('pdf-bytes'));

        $this->assertTrue($this->remoteFiles()->exists('files/a.pdf'));
        $this->assertFalse($this->localFiles()->exists('files/a.pdf'));
    }

    public function test_read_order_follows_the_mode(): void
    {
        $this->localFiles()->put('files/local.pdf', 'local');
        $this->remoteFiles()->put('files/remote.pdf', 'remote');

        $this->assertTrue($this->storage->exists(FicheStorage::FILES, 'files/local.pdf'));
        $this->assertFalse($this->storage->exists(FicheStorage::FILES, 'files/remote.pdf'));

        $this->useFicheStorageMode('mirror');
        $this->assertSame('local', $this->storage->locate(FicheStorage::FILES, 'files/local.pdf')['location']);
        $this->assertSame('remote', $this->storage->locate(FicheStorage::FILES, 'files/remote.pdf')['location']);

        $this->useFicheStorageMode('s3');
        $this->assertNull($this->storage->locate(FicheStorage::FILES, 'files/local.pdf'));
        $this->assertSame('remote', $this->storage->locate(FicheStorage::FILES, 'files/remote.pdf')['location']);
    }

    public function test_s3_fallback_prefers_the_bucket_and_records_local_fallback_reads(): void
    {
        $this->useFicheStorageMode('s3-fallback');
        $this->localFiles()->put('files/both.pdf', 'old');
        $this->remoteFiles()->put('files/both.pdf', 'new');
        $this->localFiles()->put('files/only-local.pdf', 'local');

        $this->assertSame('remote', $this->storage->locate(FicheStorage::FILES, 'files/both.pdf')['location']);
        $this->assertNull($this->register->object(FicheStorage::FILES, 'files/both.pdf'));

        $this->assertSame('local', $this->storage->locate(FicheStorage::FILES, 'files/only-local.pdf')['location']);
        $this->assertNotNull($this->register->object(FicheStorage::FILES, 'files/only-local.pdf')->fallback_hit_at);
        $this->assertSame(1, $this->register->unresolvedFallbackHits());
    }

    public function test_an_unreachable_bucket_is_not_treated_as_a_missing_object(): void
    {
        $this->useFicheStorageMode('s3-fallback');
        $this->localFiles()->put('files/a.pdf', 'local');
        $this->replaceRemoteFilesDisk(new class(...$this->remoteDiskParts()) extends RemoteFakeDisk
        {
            public function exists($path): bool
            {
                throw UnableToCheckExistence::forLocation($path);
            }
        });

        $this->expectException(UnableToCheckExistence::class);

        $this->storage->withLocalCopy(FicheStorage::FILES, 'files/a.pdf', fn () => null);
    }

    public function test_with_local_copy_uses_a_local_original_in_place_and_keeps_it(): void
    {
        $this->localFiles()->put('files/a.pptx', 'slides');

        $seen = $this->storage->withLocalCopy(FicheStorage::FILES, 'files/a.pptx', fn (string $path) => $path);

        $this->assertSame($this->localFiles()->path('files/a.pptx'), $seen);
        $this->assertFileExists($seen);
    }

    public function test_with_local_copy_streams_a_remote_object_to_a_temporary_file_with_its_extension(): void
    {
        $this->useFicheStorageMode('s3');
        $this->remoteFiles()->put('files/a.pptx', 'slides');

        $seen = $this->storage->withLocalCopy(FicheStorage::FILES, 'files/a.pptx', function (string $path) {
            $this->assertSame('slides', file_get_contents($path));
            $this->assertStringEndsWith('.pptx', $path);
            $this->assertStringStartsWith(config('fiche-storage.workspace.root'), $path);

            return $path;
        });

        $this->assertFileDoesNotExist($seen);
        $this->assertDirectoryDoesNotExist(dirname($seen));
    }

    public function test_with_local_copy_cleans_up_when_the_callback_fails(): void
    {
        $this->useFicheStorageMode('s3');
        $this->remoteFiles()->put('files/a.pdf', 'pdf');
        $seen = null;

        try {
            $this->storage->withLocalCopy(FicheStorage::FILES, 'files/a.pdf', function (string $path) use (&$seen) {
                $seen = $path;

                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException) {
        }

        $this->assertNotNull($seen);
        $this->assertFileDoesNotExist($seen);
    }

    public function test_with_local_copy_reports_a_missing_object(): void
    {
        $this->expectException(ObjectMissing::class);

        $this->storage->withLocalCopy(FicheStorage::FILES, 'files/nowhere.pdf', fn () => null);
    }

    public function test_remote_disks_refuse_local_paths(): void
    {
        $this->expectException(LogicException::class);

        $this->remoteFiles()->path('files/a.pdf');
    }

    public function test_preview_urls_follow_the_mode(): void
    {
        $this->localPreviews()->put('file-previews/1/slide-001.jpg', 'jpg');
        $this->remotePreviews()->put('file-previews/2/slide-001.jpg', 'jpg');

        $this->assertStringEndsWith('/storage/file-previews/1/slide-001.jpg', $this->storage->previewUrl('file-previews/1/slide-001.jpg'));

        $this->useFicheStorageMode('mirror');
        $this->assertStringEndsWith('/storage/file-previews/1/slide-001.jpg', $this->storage->previewUrl('file-previews/1/slide-001.jpg'));
        $this->assertSame('https://hartverwarmers-previews.objects.test/file-previews/2/slide-001.jpg', $this->storage->previewUrl('file-previews/2/slide-001.jpg'));

        $this->useFicheStorageMode('s3-fallback');
        $this->assertSame('https://hartverwarmers-previews.objects.test/file-previews/2/slide-001.jpg', $this->storage->previewUrl('file-previews/2/slide-001.jpg'));
        $this->assertStringEndsWith('/storage/file-previews/1/slide-001.jpg', $this->storage->previewUrl('file-previews/1/slide-001.jpg'));

        $this->useFicheStorageMode('s3');
        $this->assertSame('https://hartverwarmers-previews.objects.test/file-previews/1/slide-001.jpg', $this->storage->previewUrl('file-previews/1/slide-001.jpg'));
    }

    public function test_card_previews_use_bucket_thumbnails_in_s3_mode(): void
    {
        $this->useFicheStorageMode('s3');
        $fiche = Fiche::factory()->published()->create();
        File::factory()->for($fiche)->create(['preview_images' => ['file-previews/9/slide-001.jpg'], 'total_slides' => 1]);

        $urls = $fiche->load('files')->cardPreviewImages();

        $this->assertSame(['https://hartverwarmers-previews.objects.test/file-previews/9/slide-001-thumb.jpg'], $urls);
    }

    public function test_ordinary_delete_keeps_versions_and_purge_removes_them(): void
    {
        $this->useFicheStorageMode('mirror');
        foreach (['files/a.pdf', 'files/b.pdf'] as $path) {
            $this->localFiles()->put($path, 'x');
            $this->remoteFiles()->put($path, 'x');
        }

        $this->storage->delete(FicheStorage::FILES, ['files/a.pdf']);
        $this->storage->purge(FicheStorage::FILES, ['files/b.pdf']);

        foreach (['files/a.pdf', 'files/b.pdf'] as $path) {
            $this->assertFalse($this->localFiles()->exists($path));
            $this->assertFalse($this->remoteFiles()->exists($path));
            $this->assertSame(MigrationRegister::STATE_DELETED, $this->register->object(FicheStorage::FILES, $path)->state);
        }
        $this->assertSame(['files/b.pdf'], $this->objectVersions->purged);
    }

    public function test_delete_prunes_emptied_per_file_directories_only(): void
    {
        $this->localPreviews()->put('file-previews/7/slide-001.jpg', 'jpg');
        $this->localPreviews()->put('file-previews/8/slide-001.jpg', 'jpg');

        $this->storage->delete(FicheStorage::PREVIEWS, ['file-previews/7/slide-001.jpg']);

        $this->assertFalse($this->localPreviews()->directoryExists('file-previews/7'));
        $this->assertTrue($this->localPreviews()->directoryExists('file-previews'));
        $this->assertTrue($this->localPreviews()->exists('file-previews/8/slide-001.jpg'));
    }

    public function test_attachment_disposition_never_contains_the_raw_name(): void
    {
        $header = FicheStorage::attachmentDisposition("Muziek \"bingo\"\r\n café/ü.pdf");

        $this->assertStringNotContainsString("\r", $header);
        $this->assertStringNotContainsString("\n", $header);
        $this->assertStringContainsString("filename*=utf-8''", $header);
        $this->assertMatchesRegularExpression('/^attachment; filename="[\x20-\x7E]+"/', $header);
    }

    public function test_an_unknown_mode_is_rejected(): void
    {
        $this->useFicheStorageMode('cloud');

        $this->expectExceptionMessage("Unknown FICHE_STORAGE_MODE 'cloud'");

        $this->storage->mode();
    }

    private function tempFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fiche-test-');
        file_put_contents($path, $contents);

        return $path;
    }

    /**
     * @return array{0: FilesystemOperator, 1: FilesystemAdapter, 2: array<string, mixed>}
     */
    private function remoteDiskParts(): array
    {
        $disk = $this->remoteFiles();

        return [$disk->getDriver(), $disk->getAdapter(), ['bucket' => 'hartverwarmers-files', 'throw' => true]];
    }

    private function replaceRemoteFilesDisk(RemoteFakeDisk $disk): void
    {
        Storage::set('fiche-files-s3', $disk);
    }
}
