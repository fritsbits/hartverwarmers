<?php

namespace Tests\Feature\FicheStorage;

use App\Services\FicheStorage\MigrationRegister;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TransitionCheckTest extends TestCase
{
    use PreparesFicheStorageScenarios;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFicheStorage('local');
        $this->fakeProcesses([]);
    }

    /**
     * @param  array<string, string>  $options
     * @return array{0: int, 1: string}
     */
    private function runCheck(array $options = []): array
    {
        $code = Artisan::call('files:storage-check', $options);

        return [$code, Artisan::output()];
    }

    /**
     * Mirror observed, drained, everything verified and buckets reachable.
     */
    private function readyToLeaveMirror(): void
    {
        $this->observeMode('mirror', 3600, 1800);
        $this->passSmokeTest();
        $this->putSentinels();
        $this->finishedRun('to-remote');
    }

    /**
     * s3-fallback observed for 15 days, drained, everything verified.
     */
    private function readyToLeaveFallback(): void
    {
        $this->observeMode('s3-fallback', 15 * 86400, 14 * 86400);
        $this->register()->putMeta('first_switch_at', now()->timestamp - 15 * 86400);
        $this->register()->putMeta('s3_authoritative_since', now()->timestamp - 15 * 86400);
        $this->putSentinels();
        $this->finishedRun('to-remote');
    }

    public function test_unchanged_mode_passes(): void
    {
        [$code, $output] = $this->runCheck(['--target' => 'local']);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('mag live', $output);
    }

    public function test_target_defaults_to_the_configured_mode(): void
    {
        $this->useFicheStorageMode('s3-fallback');

        [$code, $output] = $this->runCheck();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('s3-fallback mag nog niet live', $output);
    }

    public function test_unknown_target_fails(): void
    {
        [$code, $output] = $this->runCheck(['--target' => 'banaan']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Onbekende modus', $output);
    }

    public function test_same_remote_mode_fails_when_credentials_are_missing(): void
    {
        $this->observeMode('mirror');
        config(['filesystems.disks.fiche-files-s3.key' => null]);

        [$code, $output] = $this->runCheck(['--target' => 'mirror']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('FICHE_STORAGE_', $output);
    }

    public function test_same_remote_mode_passes_with_credentials(): void
    {
        $this->observeMode('mirror');

        [$code] = $this->runCheck(['--target' => 'mirror']);

        $this->assertSame(0, $code);
    }

    public function test_local_to_mirror_is_blocked_without_a_passed_smoketest(): void
    {
        [$code, $output] = $this->runCheck(['--target' => 'mirror']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('buckettest is niet geslaagd', $output);
    }

    public function test_a_failed_smoketest_still_blocks_mirror(): void
    {
        $this->register()->putMeta('smoketest', ['status' => 'blocked']);

        [$code] = $this->runCheck(['--target' => 'mirror']);

        $this->assertSame(1, $code);
    }

    public function test_local_to_mirror_passes_after_a_passed_smoketest(): void
    {
        $this->passSmokeTest();

        [$code, $output] = $this->runCheck(['--target' => 'mirror']);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('nu waargenomen: local', $output);
    }

    public function test_local_to_s3_fallback_is_refused(): void
    {
        $this->passSmokeTest();

        [$code, $output] = $this->runCheck(['--target' => 's3-fallback']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Van local kan alleen naar mirror', $output);
    }

    public function test_local_to_s3_is_refused(): void
    {
        $this->passSmokeTest();

        [$code, $output] = $this->runCheck(['--target' => 's3']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Naar s3 kan alleen vanuit s3-fallback', $output);
    }

    public function test_mirror_to_s3_fallback_passes_when_everything_is_verified(): void
    {
        $this->readyToLeaveMirror();

        [$code, $output] = $this->runCheck(['--target' => 's3-fallback']);

        $this->assertSame(0, $code, $output);
    }

    public function test_mirror_to_s3_fallback_is_blocked_until_writers_are_drained(): void
    {
        $this->readyToLeaveMirror();
        $this->observeMode('mirror', 3600, null);

        [$code, $output] = $this->runCheck(['--target' => 's3-fallback']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Oude processen zijn nog niet uitgelopen', $output);
    }

    public function test_mirror_to_s3_fallback_is_blocked_without_a_run(): void
    {
        $this->observeMode('mirror', 3600, 1800);
        $this->passSmokeTest();
        $this->putSentinels();

        [$code, $output] = $this->runCheck(['--target' => 's3-fallback']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Geen geslaagde verificatie', $output);
    }

    public function test_mirror_to_s3_fallback_is_blocked_when_the_run_is_not_verified(): void
    {
        $this->readyToLeaveMirror();
        $this->travel(2)->seconds();
        $this->finishedRun('to-remote', 'blocked');

        [$code, $output] = $this->runCheck(['--target' => 's3-fallback']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Geen geslaagde verificatie', $output);
    }

    public function test_mirror_to_s3_fallback_is_blocked_when_the_final_round_predates_the_drain(): void
    {
        $this->readyToLeaveMirror();
        $this->travel(2)->seconds();
        $this->finishedRun('to-remote', 'verified', 2400);

        [$code, $output] = $this->runCheck(['--target' => 's3-fallback']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Geen geslaagde verificatie', $output);
    }

    public function test_mirror_to_s3_fallback_is_blocked_by_unverified_objects(): void
    {
        $this->readyToLeaveMirror();
        $this->register()->recordWrite('files', 'files/new.pdf', hash('sha256', 'x'), 1);

        [$code, $output] = $this->runCheck(['--target' => 's3-fallback']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('1 object(en) wachten nog op verificatie', $output);
    }

    public function test_mirror_to_s3_fallback_is_blocked_by_an_unhealthy_bucket(): void
    {
        $this->readyToLeaveMirror();
        $this->remoteFiles()->delete(config('fiche-storage.health.sentinel'));

        [$code, $output] = $this->runCheck(['--target' => 's3-fallback']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Bucket files niet bereikbaar', $output);
        $this->assertStringNotContainsString('Bucket previews niet bereikbaar', $output);
    }

    public function test_mirror_to_s3_fallback_is_blocked_without_a_passed_smoketest(): void
    {
        $this->readyToLeaveMirror();
        $this->register()->putMeta('smoketest', null);

        [$code, $output] = $this->runCheck(['--target' => 's3-fallback']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('buckettest', $output);
    }

    public function test_s3_fallback_to_s3_passes_after_the_retention_period(): void
    {
        $this->readyToLeaveFallback();

        [$code, $output] = $this->runCheck(['--target' => 's3']);

        $this->assertSame(0, $code, $output);
    }

    public function test_s3_fallback_to_s3_is_blocked_before_the_retention_period(): void
    {
        $this->readyToLeaveFallback();
        $this->register()->putMeta('first_switch_at', now()->timestamp - 5 * 86400);

        [$code, $output] = $this->runCheck(['--target' => 's3']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('bewaartermijn', $output);
    }

    public function test_s3_fallback_to_s3_is_blocked_without_a_first_switch_moment(): void
    {
        $this->readyToLeaveFallback();
        app(MigrationRegister::class)->connection()->table('meta')->where('key', 'first_switch_at')->delete();

        [$code, $output] = $this->runCheck(['--target' => 's3']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('bewaartermijn', $output);
    }

    public function test_s3_fallback_to_s3_is_blocked_by_unresolved_fallback_hits(): void
    {
        $this->readyToLeaveFallback();
        $this->register()->upsertObject('files', 'files/a.pdf', [
            'state' => 'verified',
            'verified_at' => now()->timestamp - 100,
            'fallback_hit_at' => now()->timestamp - 50,
        ]);

        [$code, $output] = $this->runCheck(['--target' => 's3']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('lokaal gelezen', $output);
    }

    public function test_a_fallback_hit_before_the_last_verification_does_not_block(): void
    {
        $this->readyToLeaveFallback();
        $this->register()->upsertObject('files', 'files/a.pdf', [
            'state' => 'verified',
            'verified_at' => now()->timestamp - 50,
            'fallback_hit_at' => now()->timestamp - 100,
        ]);

        [$code, $output] = $this->runCheck(['--target' => 's3']);

        $this->assertSame(0, $code, $output);
    }

    public function test_s3_is_only_reachable_from_s3_fallback(): void
    {
        $this->readyToLeaveMirror();

        [$code, $output] = $this->runCheck(['--target' => 's3']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Naar s3 kan alleen vanuit s3-fallback', $output);
    }

    public function test_back_to_local_is_free_before_the_bucket_became_authoritative(): void
    {
        $this->observeMode('mirror');

        [$code] = $this->runCheck(['--target' => 'local']);

        $this->assertSame(0, $code);
    }

    public function test_back_to_local_after_authoritative_requires_mirror(): void
    {
        $this->readyToLeaveFallback();

        [$code, $output] = $this->runCheck(['--target' => 'local']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Ga eerst terug naar mirror', $output);
    }

    public function test_back_to_local_after_authoritative_requires_a_verified_to_local_run(): void
    {
        $this->observeMode('mirror', 3600, 1800);
        $this->register()->putMeta('s3_authoritative_since', now()->timestamp - 86400);

        [$code, $output] = $this->runCheck(['--target' => 'local']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Geen geslaagde verificatie', $output);
        $this->assertStringContainsString('files:copy-to-local', $output);
    }

    public function test_back_to_local_passes_with_a_verified_to_local_run(): void
    {
        $this->observeMode('mirror', 3600, 1800);
        $this->register()->putMeta('s3_authoritative_since', now()->timestamp - 86400);
        $this->finishedRun('to-local');

        [$code, $output] = $this->runCheck(['--target' => 'local']);

        $this->assertSame(0, $code, $output);
    }

    public function test_a_verified_to_remote_run_does_not_satisfy_the_way_back(): void
    {
        $this->observeMode('mirror', 3600, 1800);
        $this->register()->putMeta('s3_authoritative_since', now()->timestamp - 86400);
        $this->finishedRun('to-remote');

        [$code] = $this->runCheck(['--target' => 'local']);

        $this->assertSame(1, $code);
    }
}
