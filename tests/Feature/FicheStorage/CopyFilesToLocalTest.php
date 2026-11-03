<?php

namespace Tests\Feature\FicheStorage;

use App\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CopyFilesToLocalTest extends TestCase
{
    use PreparesFicheStorageScenarios, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFicheStorage('mirror');
        $this->fakeProcesses([]);
    }

    private function referenced(string $path): File
    {
        return File::factory()->create(['path' => $path]);
    }

    public function test_it_restores_missing_and_different_local_copies_and_verifies_them(): void
    {
        $this->observeMode('mirror', 3600, 1800);

        $this->referenced('files/missing.pdf');
        $this->referenced('files/different.pdf');
        $this->referenced('files/same.pdf');
        $this->remoteFiles()->put('files/missing.pdf', 'remote-missing');
        $this->remoteFiles()->put('files/different.pdf', 'remote-different');
        $this->remoteFiles()->put('files/same.pdf', 'identical');
        $this->localFiles()->put('files/different.pdf', 'stale-local-bytes');
        $this->localFiles()->put('files/same.pdf', 'identical');

        $this->artisan('files:copy-to-local', ['--queued' => true])->assertExitCode(0);

        $this->assertSame('remote-missing', $this->localFiles()->get('files/missing.pdf'));
        $this->assertSame('remote-different', $this->localFiles()->get('files/different.pdf'));
        $this->assertSame('identical', $this->localFiles()->get('files/same.pdf'));

        foreach (['files/missing.pdf', 'files/different.pdf', 'files/same.pdf'] as $path) {
            $object = $this->register()->object('files', $path);
            $this->assertSame('verified', $object->local_state, $path);
            $this->assertNotNull($object->local_verified_at, $path);
        }

        $run = $this->register()->latestRun('to-local');
        $this->assertSame('verified', $run->status);
        $this->assertSame('done', $run->phase);
        $this->assertSame(3, json_decode($run->summary, true)['states']['verified']);
    }

    public function test_it_restores_previews_too(): void
    {
        $this->observeMode('mirror', 3600, 1800);
        $file = File::factory()->create(['path' => 'files/a.pdf']);
        $preview = "file-previews/{$file->id}/slide-001.jpg";
        $thumb = "file-previews/{$file->id}/slide-001-thumb.jpg";
        $file->update(['preview_images' => [$preview]]);
        $this->remoteFiles()->put('files/a.pdf', 'pdf');
        $this->remotePreviews()->put($preview, 'jpg');
        $this->remotePreviews()->put($thumb, 'thumb');

        $this->artisan('files:copy-to-local', ['--queued' => true])->assertExitCode(0);

        $this->assertSame('jpg', $this->localPreviews()->get($preview));
        $this->assertSame('thumb', $this->localPreviews()->get($thumb));
        $this->assertSame('verified', $this->register()->latestRun('to-local')->status);
    }

    public function test_the_run_waits_while_old_processes_are_not_drained(): void
    {
        $this->observeMode('mirror', 60, null);
        $this->referenced('files/a.pdf');
        $this->remoteFiles()->put('files/a.pdf', 'remote');

        $this->artisan('files:copy-to-local', ['--queued' => true])->assertExitCode(0);

        $this->assertSame('remote', $this->localFiles()->get('files/a.pdf'));
        $run = $this->register()->latestRun('to-local');
        $this->assertSame('waiting', $run->status);
        $this->assertStringContainsString('Wacht tot oude processen', $run->last_error);
    }

    public function test_it_marks_an_object_failed_when_neither_side_has_it(): void
    {
        $this->observeMode('mirror', 3600, 1800);
        $this->referenced('files/lost.pdf');

        $this->artisan('files:copy-to-local', ['--queued' => true])->assertExitCode(0);

        $this->assertNotSame('verified', $this->register()->latestRun('to-local')->status);
        $this->assertSame('failed', $this->register()->object('files', 'files/lost.pdf')->local_state);
    }

    public function test_it_verifies_a_local_only_copy_without_an_expected_checksum(): void
    {
        $this->observeMode('mirror', 3600, 1800);
        $this->referenced('files/localonly.pdf');
        $this->localFiles()->put('files/localonly.pdf', 'local');

        $this->artisan('files:copy-to-local', ['--queued' => true])->assertExitCode(0);

        $this->assertSame('local', $this->localFiles()->get('files/localonly.pdf'));
        $this->assertSame('verified', $this->register()->object('files', 'files/localonly.pdf')->local_state);
    }

    public function test_it_refuses_outside_mirror_mode(): void
    {
        foreach (['local', 's3-fallback', 's3'] as $mode) {
            $this->useFicheStorageMode($mode);

            $this->artisan('files:copy-to-local', ['--queued' => true])
                ->expectsOutputToContain('alleen in modus mirror')
                ->assertExitCode(1);
        }

        $this->assertNull($this->register()->latestRun('to-local'));
    }

    public function test_it_refuses_without_remote_credentials(): void
    {
        config(['filesystems.disks.fiche-previews-s3.secret' => null]);

        $this->artisan('files:copy-to-local', ['--queued' => true])->assertExitCode(1);
    }

    public function test_it_requires_the_queued_flag(): void
    {
        $this->artisan('files:copy-to-local')
            ->expectsOutputToContain('--queued')
            ->assertExitCode(1);

        $this->assertNull($this->register()->latestRun('to-local'));
    }

    public function test_it_refuses_while_a_run_in_the_other_direction_is_active(): void
    {
        $this->register()->createRun('to-remote');

        $this->artisan('files:copy-to-local', ['--queued' => true])
            ->expectsOutputToContain('loopt al een to-remote-run')
            ->assertExitCode(1);
    }
}
