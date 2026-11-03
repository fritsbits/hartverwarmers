<?php

namespace Tests\Feature\FicheStorage;

use App\Models\Fiche;
use App\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanupLocalFilesTest extends TestCase
{
    use PreparesFicheStorageScenarios, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFicheStorage('s3');
        $this->fakeProcesses([]);
    }

    /**
     * Every precondition for cleanup holds: mode s3 observed, retention over,
     * drained, a verified to-remote run, healthy buckets.
     */
    private function readyForCleanup(): void
    {
        $this->observeMode('s3', 20 * 86400, 19 * 86400);
        $this->register()->putMeta('first_switch_at', now()->timestamp - 20 * 86400);
        $this->register()->putMeta('s3_authoritative_since', now()->timestamp - 20 * 86400);
        $this->putSentinels();
        $this->finishedRun('to-remote', 'verified', 18 * 86400);
    }

    /**
     * A referenced file that exists on both sides and is verified in the register.
     */
    private function verifiedFile(string $path, string $content = 'bytes'): File
    {
        $file = File::factory()->create(['path' => $path]);

        $this->localFiles()->put($path, $content);
        $this->remoteFiles()->put($path, $content);
        $this->register()->upsertObject('files', $path, [
            'state' => 'verified',
            'expected_sha256' => hash('sha256', $content),
            'verified_at' => now()->timestamp,
        ]);

        return $file;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{0: int, 1: string}
     */
    private function cleanup(array $options): array
    {
        $code = Artisan::call('files:cleanup-local', $options);

        return [$code, Artisan::output()];
    }

    private function planId(): string
    {
        return $this->register()->latestRun('cleanup')->id;
    }

    public function test_dry_run_is_refused_in_a_mode_other_than_s3(): void
    {
        $this->readyForCleanup();
        $this->useFicheStorageMode('s3-fallback');

        [$code, $output] = $this->cleanup(['--dry-run' => true]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('alleen in modus s3', $output);
        $this->assertNull($this->register()->latestRun('cleanup'));
    }

    public function test_dry_run_is_refused_before_the_scheduler_observed_s3(): void
    {
        $this->readyForCleanup();
        $this->observeMode('s3-fallback', 20 * 86400, 19 * 86400);

        [$code, $output] = $this->cleanup(['--dry-run' => true]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('alleen in modus s3', $output);
    }

    public function test_dry_run_is_refused_before_the_retention_period(): void
    {
        $this->readyForCleanup();
        $this->register()->putMeta('first_switch_at', now()->timestamp - 3 * 86400);

        [$code, $output] = $this->cleanup(['--dry-run' => true]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('bewaartermijn', $output);
    }

    public function test_dry_run_is_refused_without_a_verified_run(): void
    {
        $this->readyForCleanup();
        $this->travel(2)->seconds();
        $this->finishedRun('to-remote', 'blocked', 18 * 86400);

        [$code, $output] = $this->cleanup(['--dry-run' => true]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('geen geslaagde verificatie', $output);
    }

    public function test_dry_run_is_refused_when_no_run_exists_at_all(): void
    {
        $this->observeMode('s3', 20 * 86400, 19 * 86400);
        $this->register()->putMeta('first_switch_at', now()->timestamp - 20 * 86400);
        $this->putSentinels();

        [$code, $output] = $this->cleanup(['--dry-run' => true]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('geen geslaagde verificatie', $output);
    }

    public function test_dry_run_is_refused_while_objects_are_unverified(): void
    {
        $this->readyForCleanup();
        $this->register()->recordWrite('files', 'files/new.pdf', hash('sha256', 'x'), 1);

        [$code, $output] = $this->cleanup(['--dry-run' => true]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('niet geverifieerd in de bucket', $output);
    }

    public function test_dry_run_is_refused_when_a_bucket_is_unreachable(): void
    {
        $this->readyForCleanup();
        $this->remotePreviews()->delete(config('fiche-storage.health.sentinel'));

        [$code, $output] = $this->cleanup(['--dry-run' => true]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('bucket is niet bereikbaar', $output);
    }

    public function test_dry_run_is_refused_when_not_drained(): void
    {
        $this->readyForCleanup();
        $this->observeMode('s3', 20 * 86400, null);

        [$code, $output] = $this->cleanup(['--dry-run' => true]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('niet uitgelopen', $output);
    }

    public function test_dry_run_plans_verified_copies_legacy_zips_and_reports_unknown_files(): void
    {
        $this->readyForCleanup();
        $this->verifiedFile('files/one.pdf', 'one');
        $this->verifiedFile('files/two.pdf', 'two-two');
        $this->verifiedFile('files/remote-only.pdf', 'remote-only');
        $this->localFiles()->delete('files/remote-only.pdf');

        Fiche::factory()->create(['zip_path' => 'fiche-zips/old.zip']);
        Fiche::factory()->create(['zip_path' => 'fiche-zips/already-gone.zip']);
        $this->localFiles()->put('fiche-zips/old.zip', 'zip');
        $this->localFiles()->put('files/unknown.bin', 'mystery');

        [$code, $output] = $this->cleanup(['--dry-run' => true]);

        $this->assertSame(0, $code, $output);
        $planId = $this->planId();
        $this->assertStringContainsString("Plan {$planId}: 3 bestand(en)", $output);
        $this->assertStringContainsString('1 onbekend(e) bestand(en)', $output);
        $this->assertStringContainsString('files/unknown.bin', $output);
        $this->assertStringContainsString("--execute --run={$planId}", $output);

        $items = $this->register()->cleanupItems()->where('run_id', $planId)->get();
        $this->assertEqualsCanonicalizing(
            ['files/one.pdf', 'files/two.pdf', 'fiche-zips/old.zip'],
            $items->pluck('path')->all(),
        );
        $this->assertSame('legacy-zip', $items->firstWhere('path', 'fiche-zips/old.zip')->role);
        $this->assertSame('planned', $this->register()->run($planId)->status);

        $this->assertTrue($this->localFiles()->exists('files/one.pdf'));
        $this->assertTrue($this->localFiles()->exists('fiche-zips/old.zip'));
    }

    public function test_dry_run_with_nothing_to_clean_still_succeeds(): void
    {
        $this->readyForCleanup();

        [$code, $output] = $this->cleanup(['--dry-run' => true]);

        $this->assertSame(0, $code, $output);
        $this->assertStringContainsString('0 bestand(en)', $output);
    }

    public function test_execute_deletes_exactly_the_planned_verified_files_and_the_legacy_zip(): void
    {
        $this->readyForCleanup();
        $publicDisk = Storage::fake('public');
        $publicDisk->put('avatars/x.jpg', 'avatar');

        $this->verifiedFile('files/media/12/one.pdf', 'one');
        $this->verifiedFile('files/two.pdf', 'two');
        Fiche::factory()->create(['zip_path' => 'fiche-zips/old.zip']);
        $this->localFiles()->put('fiche-zips/old.zip', 'zip');
        $this->localFiles()->put('files/unknown.bin', 'mystery');

        $this->cleanup(['--dry-run' => true]);
        $planId = $this->planId();
        $this->verifiedFile('files/added-after-plan.pdf', 'late');

        [$code, $output] = $this->cleanup(['--execute' => true, '--run' => $planId]);

        $this->assertSame(0, $code, $output);
        $this->assertFalse($this->localFiles()->exists('files/media/12/one.pdf'));
        $this->assertFalse($this->localFiles()->exists('files/two.pdf'));
        $this->assertFalse($this->localFiles()->exists('fiche-zips/old.zip'));
        $this->assertTrue($this->localFiles()->exists('files/unknown.bin'));
        $this->assertTrue($this->localFiles()->exists('files/added-after-plan.pdf'));
        $publicDisk->assertExists('avatars/x.jpg');

        $this->assertTrue($this->remoteFiles()->exists('files/media/12/one.pdf'));
        $this->assertTrue($this->remoteFiles()->exists('files/two.pdf'));
        $this->assertFalse($this->localFiles()->directoryExists('files/media/12'));

        $run = $this->register()->run($planId);
        $this->assertSame('done', $run->status);
        $summary = json_decode($run->summary, true);
        $this->assertSame(3, $summary['deleted']);
        $this->assertSame(0, $summary['skipped']);
        $this->assertSame(
            3,
            $this->register()->cleanupItems()->where('run_id', $planId)->where('state', 'deleted')->count(),
        );
    }

    public function test_execute_skips_an_item_whose_remote_bytes_no_longer_match(): void
    {
        $this->readyForCleanup();
        $this->verifiedFile('files/good.pdf', 'good');
        $this->verifiedFile('files/changed.pdf', 'original');

        $this->cleanup(['--dry-run' => true]);
        $planId = $this->planId();
        $this->remoteFiles()->put('files/changed.pdf', 'tampered');

        [$code, $output] = $this->cleanup(['--execute' => true, '--run' => $planId]);

        $this->assertSame(0, $code, $output);
        $this->assertFalse($this->localFiles()->exists('files/good.pdf'));
        $this->assertTrue($this->localFiles()->exists('files/changed.pdf'));
        $this->assertSame('original', $this->localFiles()->get('files/changed.pdf'));

        $skipped = $this->register()->cleanupItems()->where('run_id', $planId)->where('path', 'files/changed.pdf')->first();
        $this->assertSame('skipped', $skipped->state);
        $this->assertStringContainsString('Remote integriteit', $skipped->reason);
        $this->assertSame('failed', $this->register()->object('files', 'files/changed.pdf')->state);
        $this->assertSame(1, json_decode($this->register()->run($planId)->summary, true)['skipped']);
    }

    public function test_execute_skips_an_item_that_is_no_longer_referenced(): void
    {
        $this->readyForCleanup();
        $file = $this->verifiedFile('files/orphaned.pdf', 'orphan');

        $this->cleanup(['--dry-run' => true]);
        $planId = $this->planId();
        $file->forceDelete();

        $this->cleanup(['--execute' => true, '--run' => $planId]);

        $this->assertTrue($this->localFiles()->exists('files/orphaned.pdf'));
    }

    public function test_execute_is_refused_when_an_object_lost_its_verification(): void
    {
        $this->readyForCleanup();
        $this->verifiedFile('files/unverified.pdf', 'bytes');

        $this->cleanup(['--dry-run' => true]);
        $planId = $this->planId();
        $this->register()->upsertObject('files', 'files/unverified.pdf', ['state' => 'pending']);

        [$code, $output] = $this->cleanup(['--execute' => true, '--run' => $planId]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('geverifieerd in de bucket', $output);
        $this->assertTrue($this->localFiles()->exists('files/unverified.pdf'));
    }

    public function test_execute_refuses_an_outdated_plan(): void
    {
        $this->readyForCleanup();
        $this->verifiedFile('files/one.pdf');

        $this->cleanup(['--dry-run' => true]);
        $first = $this->planId();
        $this->travel(2)->seconds();
        $this->cleanup(['--dry-run' => true]);
        $second = $this->planId();
        $this->assertNotSame($first, $second);

        [$code, $output] = $this->cleanup(['--execute' => true, '--run' => $first]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('niet het recentste plan', $output);
        $this->assertTrue($this->localFiles()->exists('files/one.pdf'));
    }

    public function test_execute_refuses_an_expired_plan(): void
    {
        $this->readyForCleanup();
        $this->verifiedFile('files/one.pdf');
        $this->cleanup(['--dry-run' => true]);
        $planId = $this->planId();

        $this->travel(25)->hours();
        $this->putSentinels();

        [$code, $output] = $this->cleanup(['--execute' => true, '--run' => $planId]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('verlopen', $output);
        $this->assertTrue($this->localFiles()->exists('files/one.pdf'));
    }

    public function test_execute_refuses_a_plan_that_already_ran(): void
    {
        $this->readyForCleanup();
        $this->verifiedFile('files/one.pdf');
        $this->cleanup(['--dry-run' => true]);
        $planId = $this->planId();
        $this->cleanup(['--execute' => true, '--run' => $planId]);

        [$code, $output] = $this->cleanup(['--execute' => true, '--run' => $planId]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('heeft status done', $output);
    }

    public function test_execute_refuses_an_unknown_plan(): void
    {
        $this->readyForCleanup();

        [$code, $output] = $this->cleanup(['--execute' => true, '--run' => 'does-not-exist']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Onbekend opruimplan', $output);
    }

    public function test_execute_refuses_a_run_that_is_not_a_cleanup_plan(): void
    {
        $this->readyForCleanup();
        $runId = $this->finishedRun('to-remote');

        [$code, $output] = $this->cleanup(['--execute' => true, '--run' => $runId]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Onbekend opruimplan', $output);
    }

    public function test_execute_rechecks_the_preconditions(): void
    {
        $this->readyForCleanup();
        $this->verifiedFile('files/one.pdf');
        $this->cleanup(['--dry-run' => true]);
        $planId = $this->planId();
        $this->useFicheStorageMode('mirror');

        [$code, $output] = $this->cleanup(['--execute' => true, '--run' => $planId]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('alleen in modus s3', $output);
        $this->assertTrue($this->localFiles()->exists('files/one.pdf'));
    }

    public function test_execute_requires_a_run_id(): void
    {
        $this->readyForCleanup();

        [$code, $output] = $this->cleanup(['--execute' => true]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('--run=<id>', $output);
    }

    public function test_it_refuses_when_both_flags_are_given(): void
    {
        $this->readyForCleanup();

        [$code, $output] = $this->cleanup(['--dry-run' => true, '--execute' => true, '--run' => 'x']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Kies --dry-run of --execute', $output);
        $this->assertNull($this->register()->latestRun('cleanup'));
    }

    public function test_it_refuses_when_no_flag_is_given(): void
    {
        $this->readyForCleanup();

        [$code, $output] = $this->cleanup([]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Kies --dry-run of --execute', $output);
    }
}
