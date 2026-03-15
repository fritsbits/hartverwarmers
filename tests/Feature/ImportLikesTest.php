<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportLikesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS likes (
            id BIGINT UNSIGNED PRIMARY KEY,
            user_id INT UNSIGNED,
            likeable_id INT UNSIGNED,
            likeable_type VARCHAR(255),
            profile_id BIGINT UNSIGNED,
            created_at TIMESTAMP NULL
        )');
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS users (
            id INT UNSIGNED PRIMARY KEY,
            email VARCHAR(255),
            name VARCHAR(255),
            password VARCHAR(100),
            created_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        )');
    }

    public function test_imports_like_as_kudos_type(): void
    {
        $user = User::factory()->create(['email' => 'liker@example.com']);
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create([
            'initiative_id' => $initiative->id,
            'migration_id' => 8000,
            'kudos_count' => 0,
        ]);

        DB::connection('soulcenter')->table('users')->insert([
            'id' => 600, 'email' => 'liker@example.com', 'name' => 'Liker', 'password' => 'x', 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('likes')->insert([
            'id' => 1, 'user_id' => 600, 'likeable_id' => 8000,
            'likeable_type' => 'App\\Models\\Activity', 'created_at' => '2025-03-01 12:00:00',
        ]);

        $this->artisan('app:import-likes')->assertSuccessful();

        $this->assertDatabaseHas('likes', [
            'likeable_type' => 'App\\Models\\Fiche',
            'likeable_id' => $fiche->id,
            'user_id' => $user->id,
            'type' => 'kudos',
            'count' => 1,
        ]);

        // Verify kudos_count was recalculated
        $this->assertDatabaseHas('fiches', [
            'id' => $fiche->id,
            'kudos_count' => 1,
        ]);
    }

    public function test_skips_likes_without_matching_user(): void
    {
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create([
            'initiative_id' => $initiative->id,
            'migration_id' => 9000,
        ]);

        // Like from user_id 999 who doesn't exist in new DB
        DB::connection('soulcenter')->table('users')->insert([
            'id' => 999, 'email' => 'ghost@example.com', 'name' => 'Ghost', 'password' => 'x', 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('likes')->insert([
            'id' => 2, 'user_id' => 999, 'likeable_id' => 9000,
            'likeable_type' => 'App\\Models\\Activity', 'created_at' => now(),
        ]);

        $this->artisan('app:import-likes')->assertSuccessful();

        $this->assertDatabaseCount('likes', 0);
    }

    public function test_skips_duplicate_likes(): void
    {
        $user = User::factory()->create(['email' => 'duper@example.com']);
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create([
            'initiative_id' => $initiative->id,
            'migration_id' => 10000,
        ]);

        DB::connection('soulcenter')->table('users')->insert([
            'id' => 700, 'email' => 'duper@example.com', 'name' => 'Duper', 'password' => 'x', 'created_at' => now(),
        ]);

        // Two likes from same user on same activity
        DB::connection('soulcenter')->table('likes')->insert([
            'id' => 3, 'user_id' => 700, 'likeable_id' => 10000,
            'likeable_type' => 'App\\Models\\Activity', 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('likes')->insert([
            'id' => 4, 'user_id' => 700, 'likeable_id' => 10000,
            'likeable_type' => 'App\\Models\\Activity', 'created_at' => now(),
        ]);

        $this->artisan('app:import-likes')->assertSuccessful();

        // Only one should be imported (unique constraint)
        $this->assertDatabaseCount('likes', 1);
    }
}
