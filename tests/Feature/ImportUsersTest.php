<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ImportUsersTest extends TestCase
{
    use RefreshDatabase;

    private string $csvPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Create temp CSV
        $this->csvPath = sys_get_temp_dir().'/test-contacts.csv';
        $csv = "id,created_at,first_name,last_name,email,unsubscribed\n";
        $csv .= "uuid1,2026-03-11 11:39:56,Lotte,Boussier,lotte@example.com,false\n";
        $csv .= "uuid2,2026-03-11 11:39:56,Goele,Martens,goele@example.com,false\n";
        file_put_contents($this->csvPath, $csv);

        // Create old users table in soulcenter
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS users (
            id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255),
            email VARCHAR(255),
            password VARCHAR(100),
            created_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        )');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->csvPath)) {
            unlink($this->csvPath);
        }
        parent::tearDown();
    }

    public function test_imports_users_from_csv_with_old_password(): void
    {
        $hashedPassword = Hash::make('old-password');
        DB::connection('soulcenter')->table('users')->insert([
            'id' => 100,
            'name' => 'Lotte Boussier',
            'email' => 'lotte@example.com',
            'password' => $hashedPassword,
            'created_at' => '2020-05-01 10:00:00',
        ]);

        $this->artisan('app:import-users', ['csv' => $this->csvPath])
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'first_name' => 'Lotte',
            'last_name' => 'Boussier',
            'email' => 'lotte@example.com',
            'role' => 'member',
        ]);

        // Verify password was copied (not re-hashed)
        $user = User::where('email', 'lotte@example.com')->first();
        $this->assertEquals($hashedPassword, $user->getRawOriginal('password'));
    }

    public function test_skips_existing_emails(): void
    {
        User::factory()->create(['email' => 'lotte@example.com', 'first_name' => 'Existing']);

        $this->artisan('app:import-users', ['csv' => $this->csvPath])
            ->assertSuccessful();

        // Should NOT overwrite existing user
        $this->assertDatabaseHas('users', [
            'email' => 'lotte@example.com',
            'first_name' => 'Existing',
        ]);
    }

    public function test_imports_additional_interactors(): void
    {
        // Create old DB tables
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS comments (
            id BIGINT UNSIGNED PRIMARY KEY,
            commentable_type VARCHAR(255),
            commentable_id BIGINT UNSIGNED,
            user_id BIGINT UNSIGNED,
            comment TEXT,
            created_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        )');
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS likes (
            id BIGINT UNSIGNED PRIMARY KEY,
            user_id INT UNSIGNED,
            likeable_id INT UNSIGNED,
            likeable_type VARCHAR(255),
            created_at TIMESTAMP NULL
        )');

        // Old user who commented but is NOT in CSV
        DB::connection('soulcenter')->table('users')->insert([
            'id' => 200,
            'name' => 'Helena De Wit',
            'email' => 'helena@example.com',
            'password' => Hash::make('secret'),
            'created_at' => '2023-01-15 09:00:00',
        ]);
        DB::connection('soulcenter')->table('comments')->insert([
            'id' => 1,
            'commentable_type' => 'App\\Models\\Activity',
            'commentable_id' => 100,
            'user_id' => 200,
            'comment' => 'Great!',
            'created_at' => '2023-06-01 12:00:00',
        ]);

        $this->artisan('app:import-users', ['csv' => $this->csvPath])
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'helena@example.com',
            'first_name' => 'Helena',
            'last_name' => 'De Wit',
        ]);
    }

    public function test_csv_contact_without_old_account_gets_random_password(): void
    {
        // goele@example.com is in CSV but NOT in old DB
        $this->artisan('app:import-users', ['csv' => $this->csvPath])
            ->assertSuccessful();

        $user = User::where('email', 'goele@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotEmpty($user->getRawOriginal('password'));
    }
}
