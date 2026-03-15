<?php

namespace Tests\Feature\Commands;

use App\Jobs\AssignFicheIcon;
use App\Models\Fiche;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AssignFicheIconsTest extends TestCase
{
    use RefreshDatabase;

    public function test_processes_fiches_with_null_icon(): void
    {
        Queue::fake();

        Fiche::factory()->count(3)->create(['icon' => null]);

        $this->artisan('fiches:assign-icons')
            ->expectsOutputToContain('Dispatching 3 jobs')
            ->assertExitCode(0);

        // 3 from backfill + 3 from observer on create = 6 total
        Queue::assertPushed(AssignFicheIcon::class, 6);
    }

    public function test_skips_fiches_with_existing_icon(): void
    {
        Queue::fake();

        Fiche::factory()->withIcon('music')->create();
        Fiche::factory()->create(['icon' => null]);

        $this->artisan('fiches:assign-icons')
            ->expectsOutputToContain('Dispatching 1 jobs')
            ->assertExitCode(0);
    }

    public function test_force_reassigns_all_fiches(): void
    {
        Queue::fake();

        Fiche::factory()->withIcon('music')->count(2)->create();

        $this->artisan('fiches:assign-icons', ['--force' => true])
            ->expectsOutputToContain('Dispatching 2 jobs')
            ->assertExitCode(0);

        // 2 from backfill + 2 from observer on create = 4 total
        Queue::assertPushed(AssignFicheIcon::class, 4);
    }

    public function test_handles_empty_database(): void
    {
        Queue::fake();

        $this->artisan('fiches:assign-icons')
            ->expectsOutputToContain('No fiches to process')
            ->assertExitCode(0);
    }
}
