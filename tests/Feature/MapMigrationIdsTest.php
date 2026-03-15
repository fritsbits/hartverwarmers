<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MapMigrationIdsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create temp table in soulcenter connection for each test
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS activities (
            id INT UNSIGNED PRIMARY KEY,
            title VARCHAR(255),
            published TINYINT DEFAULT 1,
            deleted_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL
        )');
    }

    public function test_maps_fiches_by_exact_title_match(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create();

        $fiche = Fiche::factory()->create([
            'title' => 'Sport- en spelactiviteit',
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
        ]);

        DB::connection('soulcenter')->table('activities')->insert([
            'id' => 20337,
            'title' => 'Sport- en spelactiviteit',
            'published' => 1,
            'deleted_at' => null,
            'created_at' => '2026-01-26 12:28:00',
        ]);

        $this->artisan('app:map-migration-ids')
            ->assertSuccessful();

        $this->assertDatabaseHas('fiches', [
            'id' => $fiche->id,
            'migration_id' => 20337,
        ]);
    }

    public function test_handles_duplicate_titles_by_created_at_order(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create();

        $fiche1 = Fiche::factory()->create([
            'title' => 'Muziekquiz',
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
            'created_at' => '2024-11-20 15:14:44',
        ]);
        $fiche2 = Fiche::factory()->create([
            'title' => 'Muziekquiz',
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
            'created_at' => '2025-05-06 10:53:47',
        ]);

        DB::connection('soulcenter')->table('activities')->insert([
            ['id' => 20199, 'title' => 'Muziekquiz', 'published' => 1, 'deleted_at' => null, 'created_at' => '2024-11-20 15:14:44'],
            ['id' => 19886, 'title' => 'Muziekquiz', 'published' => 1, 'deleted_at' => null, 'created_at' => '2025-05-06 10:53:47'],
        ]);

        $this->artisan('app:map-migration-ids')
            ->assertSuccessful();

        $this->assertDatabaseHas('fiches', ['id' => $fiche1->id, 'migration_id' => 20199]);
        $this->assertDatabaseHas('fiches', ['id' => $fiche2->id, 'migration_id' => 19886]);
    }

    public function test_handles_near_match_enveloppenspel(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create();

        $fiche = Fiche::factory()->create([
            'title' => 'Enveloppe spel',
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
        ]);

        DB::connection('soulcenter')->table('activities')->insert([
            'id' => 20377, 'title' => 'Enveloppenspel', 'published' => 1, 'deleted_at' => null, 'created_at' => '2026-03-01 00:00:00',
        ]);

        $this->artisan('app:map-migration-ids')
            ->assertSuccessful();

        $this->assertDatabaseHas('fiches', ['id' => $fiche->id, 'migration_id' => 20377]);
    }

    public function test_is_idempotent(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->create();

        $fiche = Fiche::factory()->create([
            'title' => 'Test fiche',
            'user_id' => $user->id,
            'initiative_id' => $initiative->id,
            'migration_id' => 99999,
        ]);

        DB::connection('soulcenter')->table('activities')->insert([
            'id' => 99999, 'title' => 'Test fiche', 'published' => 1, 'deleted_at' => null, 'created_at' => '2026-01-01 00:00:00',
        ]);

        $this->artisan('app:map-migration-ids')
            ->assertSuccessful();

        $this->assertDatabaseHas('fiches', ['id' => $fiche->id, 'migration_id' => 99999]);
    }
}
