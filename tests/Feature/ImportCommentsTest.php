<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportCommentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS comments (
            id BIGINT UNSIGNED PRIMARY KEY,
            commentable_type VARCHAR(255),
            commentable_id BIGINT UNSIGNED,
            user_id BIGINT UNSIGNED,
            comment TEXT,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
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

    public function test_imports_comment_with_correct_user_and_fiche(): void
    {
        $user = User::factory()->create(['email' => 'commenter@example.com']);
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create([
            'initiative_id' => $initiative->id,
            'migration_id' => 5000,
        ]);

        // Import user in old DB
        $importUser = User::factory()->create(['email' => 'import@hartverwarmers.be', 'first_name' => 'Hartverwarmers', 'last_name' => 'Import']);

        DB::connection('soulcenter')->table('users')->insert([
            'id' => 300, 'email' => 'commenter@example.com', 'name' => 'Test', 'password' => 'x', 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('comments')->insert([
            'id' => 1,
            'commentable_type' => 'App\\Models\\Activity',
            'commentable_id' => 5000,
            'user_id' => 300,
            'comment' => 'Heel leuk!',
            'created_at' => '2025-06-15 10:30:00',
            'updated_at' => '2025-06-15 10:30:00',
        ]);

        $this->artisan('app:import-comments')->assertSuccessful();

        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'App\\Models\\Fiche',
            'commentable_id' => $fiche->id,
            'user_id' => $user->id,
            'body' => 'Heel leuk!',
        ]);
    }

    public function test_skips_comments_with_empty_body(): void
    {
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create(['initiative_id' => $initiative->id, 'migration_id' => 6000]);

        User::factory()->create(['email' => 'import@hartverwarmers.be', 'first_name' => 'Hartverwarmers', 'last_name' => 'Import']);

        DB::connection('soulcenter')->table('users')->insert([
            'id' => 400, 'email' => 'test@example.com', 'name' => 'Test', 'password' => 'x', 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('comments')->insert([
            'id' => 2,
            'commentable_type' => 'App\\Models\\Activity',
            'commentable_id' => 6000,
            'user_id' => 400,
            'comment' => null,
            'created_at' => now(),
        ]);

        $this->artisan('app:import-comments')->assertSuccessful();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_skips_soft_deleted_comments(): void
    {
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create(['initiative_id' => $initiative->id, 'migration_id' => 7000]);

        User::factory()->create(['email' => 'import@hartverwarmers.be', 'first_name' => 'Hartverwarmers', 'last_name' => 'Import']);

        DB::connection('soulcenter')->table('users')->insert([
            'id' => 500, 'email' => 'test2@example.com', 'name' => 'Test', 'password' => 'x', 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('comments')->insert([
            'id' => 3,
            'commentable_type' => 'App\\Models\\Activity',
            'commentable_id' => 7000,
            'user_id' => 500,
            'comment' => 'Deleted comment',
            'created_at' => now(),
            'deleted_at' => now(),
        ]);

        $this->artisan('app:import-comments')->assertSuccessful();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_is_idempotent(): void
    {
        $user = User::factory()->create(['email' => 'repeat@example.com']);
        $initiative = Initiative::factory()->create();
        $fiche = Fiche::factory()->create([
            'initiative_id' => $initiative->id,
            'migration_id' => 8000,
        ]);

        User::factory()->create(['email' => 'import@hartverwarmers.be', 'first_name' => 'Hartverwarmers', 'last_name' => 'Import']);

        DB::connection('soulcenter')->table('users')->insert([
            'id' => 600, 'email' => 'repeat@example.com', 'name' => 'Test', 'password' => 'x', 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('comments')->insert([
            'id' => 4,
            'commentable_type' => 'App\\Models\\Activity',
            'commentable_id' => 8000,
            'user_id' => 600,
            'comment' => 'Twice!',
            'created_at' => '2025-09-01 10:00:00',
        ]);

        // Run twice
        $this->artisan('app:import-comments')->assertSuccessful();
        $this->artisan('app:import-comments')->assertSuccessful();

        // Should still only have 1 comment
        $this->assertDatabaseCount('comments', 1);
    }
}
