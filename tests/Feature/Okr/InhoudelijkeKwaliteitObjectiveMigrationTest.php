<?php

namespace Tests\Feature\Okr;

use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InhoudelijkeKwaliteitObjectiveMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The migration ships the OKR to production on deploy, so it has to work
     * against a database that predates it: the old objectives present, the new
     * one absent.
     */
    public function test_migration_creates_the_objective_and_kr_on_a_pre_existing_database(): void
    {
        $this->givenTheDatabaseBeforeThisMigration();

        $this->runMigration();

        $objective = Objective::where('slug', 'inhoudelijke-kwaliteit')->first();
        $this->assertNotNull($objective);
        $this->assertSame('Inhoudelijke kwaliteit', $objective->title);
        $this->assertSame(2, $objective->position);
        $this->assertNull($objective->archived_at);

        $kr = $objective->keyResults->firstWhere('metric_key', 'diamant_score_share');
        $this->assertNotNull($kr);
        $this->assertSame('Fiches met sterke diamantscore', $kr->label);
        $this->assertSame(35, $kr->target_value);
        $this->assertSame('%', $kr->target_unit);
    }

    public function test_migration_renames_and_reorders_the_existing_objectives(): void
    {
        $this->givenTheDatabaseBeforeThisMigration();

        $this->runMigration();

        $this->assertSame(
            ['presentatiekwaliteit', 'inhoudelijke-kwaliteit', 'onboarding', 'bedankjes', 'nieuwsbrief', 'reactivatie'],
            Objective::orderBy('position')->pluck('slug')->all(),
        );
        $this->assertSame(
            'Presentatiekwaliteit',
            Objective::where('slug', 'presentatiekwaliteit')->value('title'),
        );
    }

    public function test_migration_is_idempotent(): void
    {
        $this->givenTheDatabaseBeforeThisMigration();

        $this->runMigration();
        $this->runMigration();

        $this->assertSame(1, Objective::where('slug', 'inhoudelijke-kwaliteit')->count());
        $this->assertSame(1, KeyResult::where('metric_key', 'diamant_score_share')->count());
    }

    public function test_migration_no_ops_on_a_fresh_database(): void
    {
        // A database without objectives is a fresh install, seeded by OkrSeeder.
        // Without this guard the migration would seed a row into every test DB.
        $this->runMigration();

        $this->assertSame(0, Objective::count());
        $this->assertSame(0, KeyResult::count());
    }

    public function test_down_removes_the_objective_and_restores_the_old_order(): void
    {
        $this->givenTheDatabaseBeforeThisMigration();

        $this->runMigration();
        $this->migration()->down();

        $this->assertSame(0, Objective::where('slug', 'inhoudelijke-kwaliteit')->count());
        $this->assertSame(0, KeyResult::where('metric_key', 'diamant_score_share')->count());
        $this->assertSame(
            ['presentatiekwaliteit', 'onboarding', 'bedankjes', 'nieuwsbrief', 'reactivatie'],
            Objective::orderBy('position')->pluck('slug')->all(),
        );
        $this->assertSame(
            'Fichekwaliteit',
            Objective::where('slug', 'presentatiekwaliteit')->value('title'),
        );
    }

    /** The four objectives as production held them before this migration. */
    private function givenTheDatabaseBeforeThisMigration(): void
    {
        $before = [
            ['slug' => 'presentatiekwaliteit', 'title' => 'Fichekwaliteit', 'position' => 1],
            ['slug' => 'onboarding', 'title' => 'Activatie', 'position' => 2],
            ['slug' => 'bedankjes', 'title' => 'Interactie', 'position' => 3],
            ['slug' => 'nieuwsbrief', 'title' => 'Retentie', 'position' => 4],
            ['slug' => 'reactivatie', 'title' => 'Reactivatie', 'position' => 5],
        ];

        foreach ($before as $attributes) {
            Objective::create($attributes);
        }
    }

    private function runMigration(): void
    {
        $this->migration()->up();
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_08_24_114756_create_inhoudelijke_kwaliteit_objective.php');
    }
}
