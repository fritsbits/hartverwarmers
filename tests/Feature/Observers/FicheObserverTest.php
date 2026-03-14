<?php

namespace Tests\Feature\Observers;

use App\Jobs\AssignFicheIcon;
use App\Models\Fiche;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FicheObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_job_on_fiche_creation(): void
    {
        Queue::fake();

        $fiche = Fiche::factory()->create();

        Queue::assertPushed(AssignFicheIcon::class, function ($job) use ($fiche) {
            return $job->fiche->id === $fiche->id;
        });
    }

    public function test_dispatches_job_on_title_update(): void
    {
        $fiche = Fiche::withoutEvents(fn () => Fiche::factory()->create());

        Queue::fake();

        $fiche->update(['title' => 'Nieuwe titel']);

        Queue::assertPushed(AssignFicheIcon::class, function ($job) use ($fiche) {
            return $job->fiche->id === $fiche->id;
        });
    }

    public function test_does_not_dispatch_job_when_title_unchanged(): void
    {
        $fiche = Fiche::withoutEvents(fn () => Fiche::factory()->create());

        Queue::fake();

        $fiche->update(['description' => 'Updated description only']);

        Queue::assertNotPushed(AssignFicheIcon::class);
    }
}
