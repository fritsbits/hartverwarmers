<?php

namespace Tests\Feature\FicheStorage;

use App\Services\FicheStorage\ProcessInspector;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Tests\TestCase;

class ProcessInspectorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->startOfSecond());
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function fakePs(array $lines, int $exitCode = 0): void
    {
        Process::fake(['*' => Process::result(output: implode("\n", $lines)."\n", exitCode: $exitCode)]);
    }

    /**
     * @return array<int, array{pid: int, started_at: int, command: string}>|null
     */
    private function inspect(int $startedBeforeSecondsAgo = 60): ?array
    {
        return app(ProcessInspector::class)->artisanProcessesStartedBefore(now()->timestamp - $startedBeforeSecondsAgo);
    }

    public function test_it_returns_only_this_sites_processes_older_than_the_timestamp(): void
    {
        $base = base_path();
        $this->fakePs([
            '    1 10-00:00:00 /sbin/init',
            "  123  2-03:04:05 php {$base}/artisan queue:work",
            '  456       01:02 php /other/site/artisan queue:work',
            "  789    01:02:03 php {$base}/artisan files:copy-to-object-storage",
            "  790       00:30 php {$base}/artisan queue:work",
            '  791    00:20:00 php artisan queue:work',
        ]);

        $processes = $this->inspect();

        $this->assertSame([123, 789, 791], array_column($processes, 'pid'));
        $this->assertSame(now()->timestamp - (2 * 86400 + 3 * 3600 + 4 * 60 + 5), $processes[0]['started_at']);
        $this->assertSame(now()->timestamp - (3600 + 2 * 60 + 3), $processes[1]['started_at']);
        $this->assertSame(now()->timestamp - 1200, $processes[2]['started_at']);
        $this->assertSame("php {$base}/artisan queue:work", $processes[0]['command']);
    }

    public function test_it_excludes_harmless_scheduler_and_storage_commands(): void
    {
        $base = base_path();
        $this->fakePs([
            "  100  1-00:00:00 php {$base}/artisan schedule:run",
            "  101  1-00:00:00 php {$base}/artisan files:storage-supervise",
            "  102  1-00:00:00 php {$base}/artisan pail --timeout=0",
            "  103  1-00:00:00 php {$base}/artisan queue:work",
        ]);

        $this->assertSame([103], array_column($this->inspect(), 'pid'));
    }

    public function test_it_excludes_its_own_process(): void
    {
        $base = base_path();
        $this->fakePs([
            '  '.getmypid()."  1-00:00:00 php {$base}/artisan test",
            "  555  1-00:00:00 php {$base}/artisan queue:work",
        ]);

        $this->assertSame([555], array_column($this->inspect(), 'pid'));
    }

    public function test_processes_of_any_release_of_the_site_are_included(): void
    {
        $this->app->setBasePath('/srv/site/releases/42');
        $this->fakePs([
            '  321  1-00:00:00 php /srv/site/releases/41/artisan queue:work',
            '  322  1-00:00:00 php /srv/site/current/artisan queue:work',
            '  323  1-00:00:00 php /srv/site/releases/42/artisan queue:work',
            '  324  1-00:00:00 php /srv/other/releases/42/artisan queue:work',
        ]);

        $this->assertSame([321, 322, 323], array_column($this->inspect(), 'pid'));
    }

    public function test_it_ignores_lines_that_do_not_parse(): void
    {
        $base = base_path();
        $this->fakePs([
            '  PID ELAPSED COMMAND',
            'garbage',
            "  888  1-00:00:00 php {$base}/artisan queue:work",
        ]);

        $this->assertSame([888], array_column($this->inspect(), 'pid'));
    }

    public function test_it_returns_an_empty_list_when_nothing_matches(): void
    {
        $this->fakePs(['    1 10-00:00:00 /sbin/init']);

        $this->assertSame([], $this->inspect());
    }

    public function test_it_returns_null_when_the_process_list_fails(): void
    {
        $this->fakePs([], exitCode: 1);

        $this->assertNull($this->inspect());
    }

    public function test_it_returns_null_when_the_command_cannot_run(): void
    {
        Process::fake(['*' => fn () => throw new RuntimeException('ps not found')]);

        $this->assertNull($this->inspect());
    }

    public function test_it_asks_for_pid_elapsed_time_and_arguments(): void
    {
        $this->fakePs([]);

        $this->inspect();

        Process::assertRan(fn ($process) => $process->command === ['ps', '-eo', 'pid=,etime=,args=']);
    }
}
