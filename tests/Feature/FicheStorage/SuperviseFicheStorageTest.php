<?php

namespace Tests\Feature\FicheStorage;

use App\Jobs\AdvanceFicheStorageRun;
use App\Jobs\CleanupLocalFicheObjects;
use App\Jobs\SyncFicheObject;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SuperviseFicheStorageTest extends TestCase
{
    use PreparesFicheStorageScenarios;

    private string $workspaceRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFicheStorage('mirror');
        $this->fakeProcesses([]);
        Queue::fake();

        $this->workspaceRoot = sys_get_temp_dir().'/fiche-workspaces-'.uniqid();
        mkdir($this->workspaceRoot, 0700, true);
        config(['fiche-storage.workspace.root' => $this->workspaceRoot]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->workspaceRoot);

        parent::tearDown();
    }

    public function test_it_records_the_observed_mode(): void
    {
        $this->artisan('files:storage-supervise')->assertExitCode(0);

        $this->assertSame('mirror', $this->register()->meta('mode')['mode']);
    }

    public function test_it_marks_the_mode_drained_once_the_grace_period_passed(): void
    {
        $this->observeMode('mirror', 20 * 60);

        $this->artisan('files:storage-supervise')->assertExitCode(0);

        $this->assertNotNull($this->register()->meta('mode')['drained_at']);
    }

    public function test_it_removes_abandoned_workspaces_but_keeps_fresh_ones(): void
    {
        $maxAge = (int) config('fiche-storage.workspace.max_age_minutes');
        $old = time() - ($maxAge + 10) * 60;

        mkdir("{$this->workspaceRoot}/ws-old", 0700);
        file_put_contents("{$this->workspaceRoot}/ws-old/source.pdf", 'x');
        touch("{$this->workspaceRoot}/ws-old", $old);

        file_put_contents("{$this->workspaceRoot}/leftover.zip", 'x');
        touch("{$this->workspaceRoot}/leftover.zip", $old);

        mkdir("{$this->workspaceRoot}/ws-fresh", 0700);
        file_put_contents("{$this->workspaceRoot}/ws-fresh/source.pdf", 'x');

        mkdir("{$this->workspaceRoot}/ws-recent", 0700);
        touch("{$this->workspaceRoot}/ws-recent", time() - ($maxAge - 10) * 60);

        $this->artisan('files:storage-supervise')
            ->expectsOutputToContain('2 verlaten werkmap(pen) verwijderd')
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist("{$this->workspaceRoot}/ws-old");
        $this->assertFileDoesNotExist("{$this->workspaceRoot}/leftover.zip");
        $this->assertDirectoryExists("{$this->workspaceRoot}/ws-fresh");
        $this->assertFileExists("{$this->workspaceRoot}/ws-fresh/source.pdf");
        $this->assertDirectoryExists("{$this->workspaceRoot}/ws-recent");
    }

    public function test_it_creates_a_missing_workspace_root(): void
    {
        File::deleteDirectory($this->workspaceRoot);

        $this->artisan('files:storage-supervise')->assertExitCode(0);

        $this->assertDirectoryExists($this->workspaceRoot);
    }

    public function test_it_dispatches_sync_jobs_for_old_pending_writes_only(): void
    {
        $this->register()->recordWrite('files', 'files/old.pdf', hash('sha256', 'a'), 1);
        $this->travel(6)->minutes();
        $this->register()->recordWrite('files', 'files/fresh.pdf', hash('sha256', 'b'), 1);

        $this->artisan('files:storage-supervise')->assertExitCode(0);

        Queue::assertPushed(SyncFicheObject::class, 1);
        Queue::assertPushed(SyncFicheObject::class, fn (SyncFicheObject $job) => $job->role === 'files' && $job->path === 'files/old.pdf');
        $this->assertNotNull($this->register()->object('files', 'files/old.pdf')->queued_at);
    }

    public function test_it_does_not_requeue_a_recently_queued_write(): void
    {
        $this->register()->recordWrite('files', 'files/old.pdf', hash('sha256', 'a'), 1);
        $this->travel(6)->minutes();

        $this->artisan('files:storage-supervise');
        $this->artisan('files:storage-supervise');

        Queue::assertPushed(SyncFicheObject::class, 1);
    }

    public function test_it_requeues_a_write_whose_job_got_lost(): void
    {
        $this->register()->recordWrite('files', 'files/old.pdf', hash('sha256', 'a'), 1);
        $this->travel(6)->minutes();
        $this->artisan('files:storage-supervise');

        $this->travel(16)->minutes();
        $this->artisan('files:storage-supervise');

        Queue::assertPushed(SyncFicheObject::class, 2);
    }

    public function test_it_skips_verified_writes(): void
    {
        $this->register()->recordWrite('files', 'files/done.pdf', hash('sha256', 'a'), 1);
        $this->register()->upsertObject('files', 'files/done.pdf', ['state' => 'verified', 'verified_at' => now()->timestamp]);
        $this->travel(6)->minutes();

        $this->artisan('files:storage-supervise');

        Queue::assertNotPushed(SyncFicheObject::class);
    }

    public function test_it_does_not_dispatch_pending_writes_in_local_mode(): void
    {
        $this->useFicheStorageMode('local');
        $this->register()->recordWrite('files', 'files/old.pdf', hash('sha256', 'a'), 1);
        $this->travel(6)->minutes();

        $this->artisan('files:storage-supervise')->assertExitCode(0);

        Queue::assertNotPushed(SyncFicheObject::class);
    }

    public function test_it_redispatches_stale_running_runs(): void
    {
        $stale = $this->register()->createRun('to-remote');
        $this->travel(11)->minutes();
        $fresh = $this->register()->createRun('to-local');

        $this->artisan('files:storage-supervise')->assertExitCode(0);

        Queue::assertPushed(AdvanceFicheStorageRun::class, 1);
        Queue::assertPushed(AdvanceFicheStorageRun::class, fn (AdvanceFicheStorageRun $job) => $job->runId === $stale);
        Queue::assertNotPushed(AdvanceFicheStorageRun::class, fn (AdvanceFicheStorageRun $job) => $job->runId === $fresh);
    }

    public function test_it_redispatches_waiting_runs_immediately(): void
    {
        $waiting = $this->register()->createRun('to-remote', ['status' => 'waiting', 'phase' => 'settle']);

        $this->artisan('files:storage-supervise');

        Queue::assertPushed(AdvanceFicheStorageRun::class, fn (AdvanceFicheStorageRun $job) => $job->runId === $waiting);
    }

    public function test_it_ignores_finished_runs(): void
    {
        $this->finishedRun('to-remote');
        $this->travel(2)->hours();

        $this->artisan('files:storage-supervise');

        Queue::assertNotPushed(AdvanceFicheStorageRun::class);
    }

    public function test_it_resumes_a_stale_cleanup_run(): void
    {
        $cleanup = $this->register()->createRun('cleanup', ['status' => 'running', 'phase' => 'execute']);
        $this->travel(11)->minutes();

        $this->artisan('files:storage-supervise');

        Queue::assertPushed(CleanupLocalFicheObjects::class, fn (CleanupLocalFicheObjects $job) => $job->runId === $cleanup);
        Queue::assertNotPushed(AdvanceFicheStorageRun::class);
    }

    public function test_it_does_not_resume_runs_without_remote_credentials(): void
    {
        config(['filesystems.disks.fiche-files-s3.key' => null]);
        $this->register()->createRun('to-remote', ['status' => 'waiting', 'phase' => 'settle']);

        $this->artisan('files:storage-supervise')->assertExitCode(0);

        Queue::assertNothingPushed();
    }
}
