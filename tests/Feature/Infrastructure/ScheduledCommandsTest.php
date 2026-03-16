<?php

namespace Tests\Feature\Infrastructure;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ScheduledCommandsTest extends TestCase
{
    public function test_file_preview_generation_is_scheduled(): void
    {
        $this->assertCommandIsScheduled('file:generate-previews --all');
    }

    public function test_fiche_icon_assignment_is_scheduled(): void
    {
        $this->assertCommandIsScheduled('fiches:assign-icons');
    }

    public function test_orphan_cleanup_is_scheduled(): void
    {
        $this->assertCommandIsScheduled('file:cleanup-orphans');
    }

    public function test_theme_rollover_is_scheduled(): void
    {
        $this->assertCommandIsScheduled('themes:rollover');
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
