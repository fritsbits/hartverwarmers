<?php

namespace Tests\Feature\FicheStorage;

use App\Services\FicheStorage\MigrationRegister;
use App\Services\FicheStorage\ProcessInspector;
use Tests\Concerns\FakesFicheStorage;

/**
 * Shared scenario building blocks for the fiche storage tests: the register,
 * an observed mode history, a faked process list and healthy buckets.
 */
trait PreparesFicheStorageScenarios
{
    use FakesFicheStorage;

    protected function register(): MigrationRegister
    {
        return app(MigrationRegister::class);
    }

    /**
     * Put the sentinel object on both fake buckets so BucketHealth passes.
     */
    protected function putSentinels(): void
    {
        $sentinel = config('fiche-storage.health.sentinel');

        $this->remoteFiles()->put($sentinel, 'sentinel');
        $this->remotePreviews()->put($sentinel, 'sentinel');
    }

    /**
     * Record $mode as observed by the scheduler.
     */
    protected function observeMode(string $mode, int $sinceSecondsAgo = 3600, ?int $drainedSecondsAgo = null, ?string $previous = null): void
    {
        $this->register()->putMeta('mode', [
            'mode' => $mode,
            'since' => now()->timestamp - $sinceSecondsAgo,
            'drained_at' => $drainedSecondsAgo === null ? null : now()->timestamp - $drainedSecondsAgo,
            'previous' => $previous,
        ]);
    }

    /**
     * @param  array<int, array{pid: int, started_at: int, command: string}>|null  $processes
     */
    protected function fakeProcesses(?array $processes = []): void
    {
        $this->mock(ProcessInspector::class, function ($mock) use ($processes) {
            $mock->shouldReceive('artisanProcessesStartedBefore')->andReturn($processes);
        });
    }

    protected function passSmokeTest(): void
    {
        $this->register()->putMeta('smoketest', ['status' => 'passed', 'finished_at' => now()->timestamp, 'checks' => []]);
    }

    /**
     * A finished run whose last round started $roundStartedSecondsAgo.
     */
    protected function finishedRun(string $kind, string $status = 'verified', int $roundStartedSecondsAgo = 1700): string
    {
        $id = $this->register()->createRun($kind, [
            'status' => $status,
            'phase' => 'done',
            'finished_at' => now()->timestamp,
            'summary' => json_encode(['final_round_started_at' => now()->timestamp - $roundStartedSecondsAgo]),
        ]);

        return $id;
    }
}
