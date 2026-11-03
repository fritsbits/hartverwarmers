<?php

namespace Tests\Feature\FicheStorage;

use App\Enums\FicheStorageMode;
use App\Jobs\AdvanceFicheStorageRun;
use App\Jobs\GenerateFilePreview;
use App\Jobs\SyncFicheObject;
use App\Models\File;
use App\Services\FicheStorage\FicheObjectInventory;
use App\Services\FicheStorage\FicheStorage;
use App\Services\FicheStorage\MigrationRegister;
use App\Services\FicheStorage\ProcessInspector;
use App\Services\FicheStorage\StorageMigration;
use App\Services\FicheStorage\WriterDrain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\FakesFicheStorage;
use Tests\TestCase;

class StorageMigrationTest extends TestCase
{
    use FakesFicheStorage, RefreshDatabase;

    private MigrationRegister $register;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFicheStorage('mirror');
        config(['fiche-storage.migration.drain_grace_minutes' => 0]);
        $this->mock(ProcessInspector::class)->shouldReceive('artisanProcessesStartedBefore')->andReturn([]);
        $this->register = app(MigrationRegister::class);
    }

    public function test_a_run_copies_and_verifies_every_referenced_object(): void
    {
        $this->drained();
        $file = $this->fileWithPreviews('files/a.pdf', 'original', [1]);
        File::factory()->create(['path' => 'files/b.pdf', 'fiche_id' => $file->fiche_id]);
        $this->localFiles()->put('files/b.pdf', 'second');
        $this->localFiles()->put('files/unknown.pdf', 'not in the database');

        $runId = $this->artisanStart();

        $run = $this->register->run($runId);
        $this->assertSame('verified', $run->status);
        $this->assertSame('original', $this->remoteFiles()->get('files/a.pdf'));
        $this->assertSame('second', $this->remoteFiles()->get('files/b.pdf'));
        $this->assertSame('jpg-1', $this->remotePreviews()->get("file-previews/{$file->id}/slide-001.jpg"));
        $this->assertSame('thumb-1', $this->remotePreviews()->get("file-previews/{$file->id}/slide-001-thumb.jpg"));
        $this->assertFalse($this->remoteFiles()->exists('files/unknown.pdf'));

        $summary = json_decode($run->summary, true);
        $this->assertSame(4, $summary['total']);
        $this->assertSame(4, $summary['states']['verified']);
    }

    public function test_a_starting_command_never_starts_a_second_run(): void
    {
        Queue::fake();

        $first = app(StorageMigration::class)->start(StorageMigration::TO_REMOTE);
        $second = app(StorageMigration::class)->start(StorageMigration::TO_REMOTE);

        $this->assertFalse($first['resumed']);
        $this->assertTrue($second['resumed']);
        $this->assertSame($first['run_id'], $second['run_id']);
        $this->assertSame(1, $this->register->runs()->count());
    }

    public function test_an_interrupted_run_resumes_without_copying_twice(): void
    {
        $this->drained();
        config(['fiche-storage.migration.inventory_chunk' => 1]);
        $this->fileWithPreviews('files/a.pdf', 'a', []);
        $this->fileWithPreviews('files/b.pdf', 'b', []);

        $queue = app('queue');
        Queue::fake();
        $runId = app(StorageMigration::class)->start(StorageMigration::TO_REMOTE)['run_id'];
        app(StorageMigration::class)->advance($runId);

        $this->assertSame(1, $this->register->objects()->where('seen_run', $runId)->count());
        $this->assertSame(0, count($this->remoteFiles()->allFiles()));

        Queue::swap($queue);
        (new AdvanceFicheStorageRun($runId))->handle(app(StorageMigration::class));

        $this->assertSame('verified', $this->register->run($runId)->status);
        $this->assertSame(['files/a.pdf', 'files/b.pdf'], $this->remoteFiles()->allFiles());
    }

    public function test_an_equally_sized_but_different_remote_object_is_replaced_in_mirror_mode(): void
    {
        $this->drained();
        $this->fileWithPreviews('files/a.pdf', 'aaaa', []);
        $this->remoteFiles()->put('files/a.pdf', 'bbbb');

        $this->artisanStart();

        $this->assertSame('aaaa', $this->remoteFiles()->get('files/a.pdf'));
        $this->assertSame(MigrationRegister::STATE_VERIFIED, $this->register->object(FicheStorage::FILES, 'files/a.pdf')->state);
    }

    public function test_an_old_local_copy_never_overwrites_newer_remote_bytes_once_remote_is_authoritative(): void
    {
        $this->fileWithPreviews('files/a.pdf', 'old local', []);
        $this->remoteFiles()->put('files/a.pdf', 'new remote');
        $this->register->putMeta('s3_authoritative_since', now()->timestamp);
        $this->register->recordWrite(FicheStorage::FILES, 'files/a.pdf', hash('sha256', 'new remote'), 10);

        (new SyncFicheObject(FicheStorage::FILES, 'files/a.pdf'))->handle(app(FicheStorage::class), $this->register, app(FicheObjectInventory::class));

        $this->assertSame('new remote', $this->remoteFiles()->get('files/a.pdf'));
        $this->assertSame(MigrationRegister::STATE_VERIFIED, $this->register->object(FicheStorage::FILES, 'files/a.pdf')->state);
    }

    public function test_remote_bytes_that_differ_from_the_expected_checksum_fail_verification(): void
    {
        $this->useFicheStorageMode('s3');
        File::factory()->create(['path' => 'files/a.pdf']);
        $this->remoteFiles()->put('files/a.pdf', 'corrupted');
        $this->register->recordWrite(FicheStorage::FILES, 'files/a.pdf', hash('sha256', 'original'), 8);

        (new SyncFicheObject(FicheStorage::FILES, 'files/a.pdf'))->handle(app(FicheStorage::class), $this->register, app(FicheObjectInventory::class));

        $object = $this->register->object(FicheStorage::FILES, 'files/a.pdf');
        $this->assertSame(MigrationRegister::STATE_FAILED, $object->state);
        $this->assertSame(1, $this->register->unverifiedCount());
    }

    public function test_a_deleted_object_is_never_uploaded_again_by_a_late_copy_job(): void
    {
        $this->fileWithPreviews('files/a.pdf', 'a', []);
        $this->register->markDeleting(FicheStorage::FILES, ['files/a.pdf']);

        (new SyncFicheObject(FicheStorage::FILES, 'files/a.pdf'))->handle(app(FicheStorage::class), $this->register, app(FicheObjectInventory::class));

        $this->assertFalse($this->remoteFiles()->exists('files/a.pdf'));
    }

    public function test_a_missing_original_blocks_the_run_and_a_missing_preview_is_regenerated(): void
    {
        $this->drained();
        $file = File::factory()->create(['path' => 'files/gone.pdf', 'preview_images' => ['file-previews/1/slide-001.jpg']]);
        $file->update(['preview_images' => ["file-previews/{$file->id}/slide-001.jpg"]]);
        Queue::fake([GenerateFilePreview::class]);

        $runId = $this->artisanStart();

        $run = $this->register->run($runId);
        $this->assertSame('blocked', $run->status);
        $this->assertSame(MigrationRegister::STATE_MISSING, $this->register->object(FicheStorage::FILES, 'files/gone.pdf')->state);
        $this->assertContains('files/gone.pdf', json_decode($run->summary, true)['missing']);
        Queue::assertPushed(GenerateFilePreview::class, fn (GenerateFilePreview $job) => $job->fileId === $file->id);
    }

    public function test_a_run_waits_until_old_writers_are_gone_and_then_inventories_again(): void
    {
        $this->fileWithPreviews('files/a.pdf', 'a', []);
        app(WriterDrain::class)->observe(FicheStorageMode::Mirror);
        config(['fiche-storage.migration.drain_grace_minutes' => 15]);

        $runId = $this->artisanStart();

        $run = $this->register->run($runId);
        $this->assertSame('waiting', $run->status);
        $this->assertStringContainsString('Wacht tot oude processen', $run->last_error);

        $this->travel(16)->minutes();
        app(WriterDrain::class)->markDrainedIfReady();
        app(StorageMigration::class)->advance($runId);

        $run = $this->register->run($runId);
        $this->assertSame('verified', $run->status);
        $this->assertSame(2, (int) $run->round);
    }

    public function test_copy_command_requires_a_remote_mode_and_the_queued_flag(): void
    {
        $this->artisan('files:copy-to-object-storage')->assertExitCode(1);

        $this->useFicheStorageMode('local');
        $this->artisan('files:copy-to-object-storage', ['--queued' => true])->assertExitCode(1);
    }

    private function drained(): void
    {
        $drain = app(WriterDrain::class);
        $drain->observe(FicheStorageMode::Mirror);
        $this->travel(1)->minutes();
        $drain->markDrainedIfReady();
        $this->travel(1)->minutes();
        $this->assertNotNull($drain->drainedAt());
    }

    private function artisanStart(): string
    {
        $this->artisan('files:copy-to-object-storage', ['--queued' => true])->assertExitCode(0);

        return $this->register->latestRun(StorageMigration::TO_REMOTE)->id;
    }

    /**
     * @param  array<int, int>  $slides
     */
    private function fileWithPreviews(string $path, string $contents, array $slides): File
    {
        $file = File::factory()->create(['path' => $path]);
        $this->localFiles()->put($path, $contents);

        $previews = [];
        foreach ($slides as $slide) {
            $preview = sprintf('file-previews/%d/slide-%03d.jpg', $file->id, $slide);
            $this->localPreviews()->put($preview, "jpg-{$slide}");
            $this->localPreviews()->put(str_replace('.jpg', '-thumb.jpg', $preview), "thumb-{$slide}");
            $previews[] = $preview;
        }

        $file->update(['preview_images' => $previews ?: null]);

        return $file;
    }
}
