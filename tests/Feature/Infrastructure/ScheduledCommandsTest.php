<?php

namespace Tests\Feature\Infrastructure;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ScheduledCommandsTest extends TestCase
{
    /**
     * Every scheduled artisan task must resolve to a registered command.
     * A scheduled name without a command fails silently on every run,
     * so the schedule must never reference a command that does not exist.
     */
    public function test_every_scheduled_command_is_registered(): void
    {
        $registered = Artisan::all();

        $missing = collect($this->app->make(Schedule::class)->events())
            ->map(fn (Event $event): ?string => $this->scheduledCommandName($event))
            ->filter()
            ->unique()
            ->reject(fn (string $name): bool => array_key_exists($name, $registered))
            ->values();

        $this->assertTrue(
            $missing->isEmpty(),
            'Scheduled commands that are not registered in artisan: ['.$missing->implode(', ').'].'
        );
    }

    public function test_fiche_icon_assignment_is_scheduled(): void
    {
        $this->assertCommandIsScheduled('fiches:assign-icons');
    }

    public function test_orphan_cleanup_is_scheduled(): void
    {
        $this->assertCommandIsScheduled('file:cleanup-orphans');
    }

    public function test_quality_assessment_is_scheduled(): void
    {
        $this->assertCommandIsScheduled('fiches:assess-quality');
    }

    public function test_failed_jobs_prune_is_scheduled(): void
    {
        $this->assertCommandIsScheduled('queue:prune-failed');
    }

    /**
     * Extract the artisan command name from a scheduled event's shell string,
     * which looks like `'/usr/bin/php' 'artisan' name --option=x`.
     */
    private function scheduledCommandName(Event $event): ?string
    {
        $parts = preg_split('/\s+/', trim($event->command ?? ''));

        $artisanIndex = array_search('artisan', array_map(
            fn (string $part): string => trim($part, "'\""),
            $parts,
        ), true);

        if ($artisanIndex === false || ! isset($parts[$artisanIndex + 1])) {
            return null;
        }

        return trim($parts[$artisanIndex + 1], "'\"");
    }

    private function assertCommandIsScheduled(string $command): void
    {
        $schedule = $this->app->make(Schedule::class);
        $events = collect($schedule->events());

        $found = $events->contains(function ($event) use ($command) {
            return str_contains($event->command ?? '', $command);
        });

        $this->assertTrue($found, "Expected command [{$command}] to be scheduled.");
    }
}
