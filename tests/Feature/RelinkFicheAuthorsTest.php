<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RelinkFicheAuthorsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Old DB tables
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS activities (
            id INT UNSIGNED PRIMARY KEY, title VARCHAR(255), published TINYINT DEFAULT 1, deleted_at TIMESTAMP NULL, created_at TIMESTAMP NULL
        )');
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS activity_author_profile (
            activity_id INT UNSIGNED, profile_id BIGINT UNSIGNED
        )');
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS profiles (
            id BIGINT UNSIGNED PRIMARY KEY, first_name VARCHAR(255), last_name VARCHAR(255),
            type VARCHAR(255), user_id INT UNSIGNED, carehome_id INT UNSIGNED
        )');
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS authors (
            id INT UNSIGNED PRIMARY KEY, name VARCHAR(255), company VARCHAR(255),
            email VARCHAR(255), user_id INT UNSIGNED, profile_id BIGINT UNSIGNED
        )');
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS users (
            id INT UNSIGNED PRIMARY KEY, email VARCHAR(255), name VARCHAR(255),
            password VARCHAR(100), created_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL
        )');
    }

    public function test_relinks_fiche_to_real_user_via_author_user_id(): void
    {
        $stubUser = User::factory()->create(['email' => 'stub@import.hartverwarmers.be', 'organisation' => 'Import']);
        $realUser = User::factory()->create(['email' => 'real@example.com']);
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create([
            'user_id' => $stubUser->id,
            'initiative_id' => $initiative->id,
            'migration_id' => 1000,
        ]);

        // Old: activity 1000 -> author -> user with email real@example.com
        DB::connection('soulcenter')->table('activities')->insert([
            'id' => 1000, 'title' => 'Test', 'published' => 1, 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('activity_author_profile')->insert([
            'activity_id' => 1000, 'profile_id' => 5000,
        ]);
        DB::connection('soulcenter')->table('profiles')->insert([
            'id' => 5000, 'first_name' => 'Real', 'last_name' => 'User', 'type' => 'author', 'user_id' => null, 'carehome_id' => null,
        ]);
        DB::connection('soulcenter')->table('authors')->insert([
            'id' => 1, 'name' => 'Real User', 'company' => 'WZC Test', 'email' => null, 'user_id' => 500, 'profile_id' => 5000,
        ]);
        DB::connection('soulcenter')->table('users')->insert([
            'id' => 500, 'email' => 'real@example.com', 'name' => 'Real User', 'password' => 'x', 'created_at' => now(),
        ]);

        $this->artisan('app:relink-fiche-authors')->assertSuccessful();

        $this->assertDatabaseHas('fiches', ['id' => $fiche->id, 'user_id' => $realUser->id]);
    }

    public function test_updates_stub_user_organisation_from_author_company(): void
    {
        $stubUser = User::factory()->create(['email' => 'stub@import.hartverwarmers.be', 'organisation' => 'Import']);
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create([
            'user_id' => $stubUser->id,
            'initiative_id' => $initiative->id,
            'migration_id' => 2000,
        ]);

        DB::connection('soulcenter')->table('activities')->insert([
            'id' => 2000, 'title' => 'Test2', 'published' => 1, 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('activity_author_profile')->insert([
            'activity_id' => 2000, 'profile_id' => 6000,
        ]);
        DB::connection('soulcenter')->table('profiles')->insert([
            'id' => 6000, 'first_name' => 'Stub', 'last_name' => 'Author', 'type' => 'author', 'user_id' => null, 'carehome_id' => null,
        ]);
        DB::connection('soulcenter')->table('authors')->insert([
            'id' => 2, 'name' => 'Stub Author', 'company' => 'WZC Zonnestraal', 'email' => null, 'user_id' => null, 'profile_id' => 6000,
        ]);

        $this->artisan('app:relink-fiche-authors')->assertSuccessful();

        // Fiche stays with stub user (no real user found), but org is updated
        $this->assertDatabaseHas('users', ['id' => $stubUser->id, 'organisation' => 'WZC Zonnestraal']);
    }
}
