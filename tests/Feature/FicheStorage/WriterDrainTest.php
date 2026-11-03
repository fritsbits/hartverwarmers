<?php

namespace Tests\Feature\FicheStorage;

use App\Enums\FicheStorageMode;
use App\Services\FicheStorage\WriterDrain;
use ReflectionProperty;
use Tests\TestCase;

class WriterDrainTest extends TestCase
{
    use PreparesFicheStorageScenarios;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFicheStorage('local');
        $this->travelTo(now()->startOfSecond());
        $this->resetWorkerBeat();
    }

    protected function tearDown(): void
    {
        $this->resetWorkerBeat();

        parent::tearDown();
    }

    private function resetWorkerBeat(): void
    {
        $property = new ReflectionProperty(WriterDrain::class, 'lastWorkerBeat');
        $property->setValue(null, null);
    }

    private function drain(): WriterDrain
    {
        return app(WriterDrain::class);
    }

    public function test_observe_records_mode_and_since(): void
    {
        $this->drain()->observe(FicheStorageMode::Mirror);

        $meta = $this->register()->meta('mode');
        $this->assertSame('mirror', $meta['mode']);
        $this->assertSame(now()->timestamp, $meta['since']);
        $this->assertNull($meta['drained_at']);
        $this->assertNull($meta['previous']);
        $this->assertSame(FicheStorageMode::Mirror, $this->drain()->observedMode());
        $this->assertSame(now()->timestamp, $this->drain()->observedSince());
    }

    public function test_observe_keeps_since_when_the_mode_does_not_change(): void
    {
        $this->drain()->observe(FicheStorageMode::Mirror);
        $since = now()->timestamp;

        $this->travel(10)->minutes();
        $this->drain()->observe(FicheStorageMode::Mirror);

        $this->assertSame($since, $this->drain()->observedSince());
    }

    public function test_observe_keeps_drained_at_when_the_mode_does_not_change(): void
    {
        $this->observeMode('mirror', 3600, 1800);
        $drainedAt = $this->drain()->drainedAt();

        $this->drain()->observe(FicheStorageMode::Mirror);

        $this->assertSame($drainedAt, $this->drain()->drainedAt());
    }

    public function test_observe_resets_since_and_drain_on_a_mode_change(): void
    {
        $this->observeMode('mirror', 3600, 1800);

        $this->drain()->observe(FicheStorageMode::S3Fallback);

        $meta = $this->register()->meta('mode');
        $this->assertSame('s3-fallback', $meta['mode']);
        $this->assertSame(now()->timestamp, $meta['since']);
        $this->assertNull($meta['drained_at']);
        $this->assertSame('mirror', $meta['previous']);
    }

    public function test_observe_sets_the_switch_moments_when_entering_s3_fallback(): void
    {
        $this->drain()->observe(FicheStorageMode::Mirror);
        $this->assertNull($this->register()->meta('s3_authoritative_since'));
        $this->assertNull($this->register()->meta('first_switch_at'));

        $this->drain()->observe(FicheStorageMode::S3Fallback);

        $this->assertSame(now()->timestamp, $this->register()->meta('s3_authoritative_since'));
        $this->assertSame(now()->timestamp, $this->register()->meta('first_switch_at'));
    }

    public function test_first_switch_moment_is_never_overwritten(): void
    {
        $this->drain()->observe(FicheStorageMode::S3Fallback);
        $first = now()->timestamp;

        $this->travel(2)->days();
        $this->drain()->observe(FicheStorageMode::Mirror);
        $this->drain()->observe(FicheStorageMode::S3Fallback);

        $this->assertSame($first, $this->register()->meta('first_switch_at'));
        $this->assertSame($first, $this->register()->meta('s3_authoritative_since'));
    }

    public function test_entering_s3_directly_sets_authoritative_but_not_first_switch(): void
    {
        $this->drain()->observe(FicheStorageMode::S3);

        $this->assertSame(now()->timestamp, $this->register()->meta('s3_authoritative_since'));
        $this->assertNull($this->register()->meta('first_switch_at'));
    }

    public function test_observed_mode_defaults_to_local(): void
    {
        $this->assertSame(FicheStorageMode::Local, $this->drain()->observedMode());
        $this->assertNull($this->drain()->observedSince());
        $this->assertNull($this->drain()->drainedAt());
    }

    public function test_evaluate_is_not_drained_before_the_mode_was_observed(): void
    {
        $this->fakeProcesses([]);

        $result = $this->drain()->evaluate();

        $this->assertFalse($result['drained']);
        $this->assertStringContainsString('nog niet waargenomen', $result['reasons'][0]);
    }

    public function test_evaluate_is_not_drained_during_the_grace_period(): void
    {
        $this->fakeProcesses([]);
        $this->observeMode('mirror', 5 * 60);

        $result = $this->drain()->evaluate();

        $this->assertFalse($result['drained']);
        $this->assertCount(1, $result['reasons']);
        $this->assertStringContainsString('Wachttijd', $result['reasons'][0]);
    }

    public function test_evaluate_is_drained_after_the_grace_period_when_clean(): void
    {
        $this->fakeProcesses([]);
        $this->observeMode('mirror', 16 * 60);

        $result = $this->drain()->evaluate();

        $this->assertTrue($result['drained']);
        $this->assertSame([], $result['reasons']);
    }

    public function test_evaluate_is_not_drained_with_old_processes(): void
    {
        $this->fakeProcesses([
            ['pid' => 4242, 'started_at' => now()->timestamp - 7200, 'command' => 'php artisan queue:work'],
            ['pid' => 4243, 'started_at' => now()->timestamp - 7200, 'command' => 'php artisan queue:work'],
        ]);
        $this->observeMode('mirror', 3600);

        $result = $this->drain()->evaluate();

        $this->assertFalse($result['drained']);
        $this->assertStringContainsString('2 artisan-proces', $result['reasons'][0]);
        $this->assertStringContainsString('4242, 4243', $result['reasons'][0]);
    }

    public function test_evaluate_is_not_drained_when_the_process_list_is_unreadable(): void
    {
        $this->fakeProcesses(null);
        $this->observeMode('mirror', 3600);

        $result = $this->drain()->evaluate();

        $this->assertFalse($result['drained']);
        $this->assertStringContainsString('niet leesbaar', $result['reasons'][0]);
    }

    public function test_evaluate_is_not_drained_when_a_worker_reports_another_mode(): void
    {
        $this->fakeProcesses([]);
        $this->observeMode('local', 3600);
        $this->useFicheStorageMode('mirror');
        $this->drain()->recordWorker();

        $result = $this->drain()->evaluate();

        $this->assertFalse($result['drained']);
        $this->assertStringContainsString('meldt nog modus mirror', $result['reasons'][0]);
    }

    public function test_evaluate_is_drained_once_the_worker_reports_the_observed_mode(): void
    {
        $this->fakeProcesses([]);
        $this->observeMode('local', 3600);
        $this->useFicheStorageMode('mirror');
        $this->drain()->recordWorker();

        $this->resetWorkerBeat();
        $this->useFicheStorageMode('local');
        $this->drain()->recordWorker();

        $this->assertTrue($this->drain()->evaluate()['drained']);
    }

    public function test_stale_worker_heartbeats_are_ignored(): void
    {
        $this->fakeProcesses([]);
        $this->observeMode('local', 3600);
        $this->useFicheStorageMode('mirror');
        $this->drain()->recordWorker();
        $this->assertCount(1, $this->drain()->workers());

        $this->travel(11)->minutes();

        $this->assertSame([], $this->drain()->workers());
        $this->assertTrue($this->drain()->evaluate()['drained']);
    }

    public function test_worker_heartbeat_is_throttled_to_once_a_minute(): void
    {
        $this->useFicheStorageMode('mirror');
        $this->drain()->recordWorker();

        $this->useFicheStorageMode('local');
        $this->travel(30)->seconds();
        $this->drain()->recordWorker();

        $worker = collect($this->drain()->workers())->first();
        $this->assertSame('mirror', $worker['mode']);
        $this->assertSame(WriterDrain::release(), $worker['release']);

        $this->travel(31)->seconds();
        $this->drain()->recordWorker();

        $this->assertSame('local', collect($this->drain()->workers())->first()['mode']);
    }

    public function test_mark_drained_sets_drained_at_when_ready(): void
    {
        $this->fakeProcesses([]);
        $this->observeMode('mirror', 20 * 60);

        $this->drain()->markDrainedIfReady();

        $this->assertSame(now()->timestamp, $this->drain()->drainedAt());
    }

    public function test_mark_drained_waits_for_the_grace_period(): void
    {
        $this->fakeProcesses([]);
        $this->observeMode('mirror', 5 * 60);

        $this->drain()->markDrainedIfReady();

        $this->assertNull($this->drain()->drainedAt());
    }

    public function test_mark_drained_waits_for_old_processes(): void
    {
        $this->fakeProcesses([['pid' => 9, 'started_at' => 1, 'command' => 'php artisan queue:work']]);
        $this->observeMode('mirror', 3600);

        $this->drain()->markDrainedIfReady();

        $this->assertNull($this->drain()->drainedAt());
    }

    public function test_mark_drained_does_nothing_without_an_observed_mode(): void
    {
        $this->fakeProcesses([]);

        $this->drain()->markDrainedIfReady();

        $this->assertNull($this->register()->meta('mode'));
    }

    public function test_mark_drained_keeps_the_first_drained_moment(): void
    {
        $this->fakeProcesses([]);
        $this->observeMode('mirror', 3600, 1800);
        $drainedAt = $this->drain()->drainedAt();

        $this->travel(1)->hour();
        $this->drain()->markDrainedIfReady();

        $this->assertSame($drainedAt, $this->drain()->drainedAt());
    }
}
