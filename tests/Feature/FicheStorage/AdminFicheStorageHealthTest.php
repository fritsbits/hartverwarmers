<?php

namespace Tests\Feature\FicheStorage;

use App\Models\User;
use App\Services\FicheStorage\BucketHealth;
use App\Services\FicheStorage\MigrationRegister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AdminFicheStorageHealthTest extends TestCase
{
    use PreparesFicheStorageScenarios, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFicheStorage('local');
        $this->fakeProcesses([]);
    }

    private function visit()
    {
        return $this->actingAs(User::factory()->admin()->create())->get(route('admin.health'));
    }

    public function test_it_requires_an_admin(): void
    {
        $this->get(route('admin.health'))->assertRedirect();
        $this->actingAs(User::factory()->create())->get(route('admin.health'))->assertForbidden();
    }

    public function test_it_renders_the_card_with_the_mode(): void
    {
        $this->visit()
            ->assertOk()
            ->assertSee('Fiche-opslag')
            ->assertSee('Modus')
            ->assertSee('local')
            ->assertSee('Release '.basename(base_path()))
            ->assertSee('nog niet gestart');
    }

    public function test_it_shows_the_observed_mode_and_drain_state_in_mirror_mode(): void
    {
        $this->useFicheStorageMode('mirror');
        $this->observeMode('mirror', 60, null, 'local');

        $this->visit()
            ->assertOk()
            ->assertSee('mirror')
            ->assertSee('waargenomen sinds')
            ->assertSee('Oude processen uitgelopen')
            ->assertSee('Nog niet')
            ->assertSee('Wachttijd na de moduswissel');
    }

    public function test_it_shows_the_to_remote_run_status_and_counts(): void
    {
        $this->useFicheStorageMode('mirror');
        $this->observeMode('mirror', 3600, 1800, 'local');
        $this->putSentinels();

        $runId = $this->register()->createRun('to-remote', ['phase' => 'transfer', 'round' => 2]);
        $this->register()->upsertObject('files', 'files/a.pdf', ['seen_run' => $runId, 'seen_round' => 2, 'state' => 'verified', 'size' => 100]);
        $this->register()->upsertObject('files', 'files/b.pdf', ['seen_run' => $runId, 'seen_round' => 2, 'state' => 'pending', 'size' => 50]);
        $this->register()->upsertObject('previews', 'file-previews/1/s.jpg', ['seen_run' => $runId, 'seen_round' => 2, 'state' => 'missing', 'size' => 5]);

        $this->visit()
            ->assertOk()
            ->assertSee('Kopie naar de bucket')
            ->assertSee($runId)
            ->assertSee('running')
            ->assertSee('1 / 3', false)
            ->assertSee('ronde 2')
            ->assertSee('Ontbreekt: file-previews/1/s.jpg')
            ->assertSee('Objecten zonder geslaagde verificatie')
            ->assertSee('Sinds ');
    }

    public function test_it_shows_a_verified_run(): void
    {
        $this->useFicheStorageMode('mirror');
        $this->observeMode('mirror', 3600, 1800);
        $this->finishedRun('to-remote');

        $this->visit()->assertOk()->assertSee('verified');
    }

    public function test_it_shows_unreachable_buckets(): void
    {
        $this->useFicheStorageMode('mirror');
        $this->observeMode('mirror', 3600, 1800);
        $this->remotePreviews()->put(config('fiche-storage.health.sentinel'), 'sentinel');
        app(BucketHealth::class)->check();

        $this->visit()
            ->assertOk()
            ->assertSee('Bucket bestanden')
            ->assertSee('Onbereikbaar')
            ->assertSee('Sentinel ontbreekt')
            ->assertSee('Bucket previews');
    }

    public function test_it_shows_the_last_error(): void
    {
        $this->register()->recordError('Upload mislukt', 'files', 'files/x.pdf');

        $this->visit()
            ->assertOk()
            ->assertSee('Laatste fout')
            ->assertSee('files:files/x.pdf', false)
            ->assertSee('Upload mislukt');
    }

    public function test_it_shows_the_error_text_when_the_register_cannot_be_read(): void
    {
        $this->mock(MigrationRegister::class, function ($mock) {
            $mock->shouldReceive('connection')->andThrow(new RuntimeException('database is locked'));
            $mock->shouldReceive('meta')->andThrow(new RuntimeException('database is locked'));
        });

        $this->visit()
            ->assertOk()
            ->assertSee('Fiche-opslag')
            ->assertSee('Migratieregister niet leesbaar')
            ->assertSee('database is locked');
    }
}
