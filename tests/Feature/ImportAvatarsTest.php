<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportAvatarsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS authors (
            id INT UNSIGNED PRIMARY KEY, name VARCHAR(255), email VARCHAR(255),
            user_id INT UNSIGNED, profile_id BIGINT UNSIGNED, company VARCHAR(255), image VARCHAR(500)
        )');
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS media (
            id BIGINT UNSIGNED PRIMARY KEY, model_type VARCHAR(255), model_id BIGINT UNSIGNED,
            collection_name VARCHAR(255), name VARCHAR(255), file_name VARCHAR(255),
            mime_type VARCHAR(255), disk VARCHAR(255), size BIGINT UNSIGNED,
            order_column INT UNSIGNED, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL
        )');
        DB::connection('soulcenter')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS users (
            id INT UNSIGNED PRIMARY KEY, email VARCHAR(255), name VARCHAR(255),
            password VARCHAR(100), created_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL
        )');
    }

    public function test_imports_avatar_for_user_matched_by_email(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['email' => 'author@example.com', 'avatar_path' => null]);

        // Old author with user_id → old user email
        DB::connection('soulcenter')->table('users')->insert([
            'id' => 100, 'email' => 'author@example.com', 'name' => 'Test', 'password' => 'x', 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('authors')->insert([
            'id' => 1, 'name' => 'Test Author', 'email' => null, 'user_id' => 100, 'profile_id' => 5000,
        ]);
        DB::connection('soulcenter')->table('media')->insert([
            'id' => 2375, 'model_type' => 'App\\Models\\Profile', 'model_id' => 5000,
            'collection_name' => 'avatar', 'name' => 'avatar', 'file_name' => 'test.jpeg',
            'mime_type' => 'image/jpeg', 'disk' => 'media', 'size' => 15000,
            'created_at' => now(),
        ]);

        // Create source file in a temp location
        $sourcePath = sys_get_temp_dir().'/test-avatar-media/2375';
        @mkdir($sourcePath, 0755, true);
        // Create a minimal valid JPEG
        $img = imagecreatetruecolor(100, 100);
        imagejpeg($img, $sourcePath.'/test.jpeg');
        imagedestroy($img);

        $this->artisan('app:import-avatars', [
            'source' => sys_get_temp_dir().'/test-avatar-media',
        ])->assertSuccessful();

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        $this->assertTrue(Storage::disk('public')->exists($user->avatar_path));

        // Cleanup
        @unlink($sourcePath.'/test.jpeg');
        @rmdir($sourcePath);
    }

    public function test_skips_user_who_already_has_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'avatar_path' => 'avatars/existing.jpg',
        ]);

        DB::connection('soulcenter')->table('users')->insert([
            'id' => 200, 'email' => 'existing@example.com', 'name' => 'Test', 'password' => 'x', 'created_at' => now(),
        ]);
        DB::connection('soulcenter')->table('authors')->insert([
            'id' => 2, 'name' => 'Existing Author', 'email' => null, 'user_id' => 200, 'profile_id' => 6000,
        ]);
        DB::connection('soulcenter')->table('media')->insert([
            'id' => 9999, 'model_type' => 'App\\Models\\Profile', 'model_id' => 6000,
            'collection_name' => 'avatar', 'name' => 'avatar', 'file_name' => 'old.jpeg',
            'mime_type' => 'image/jpeg', 'disk' => 'media', 'size' => 10000,
            'created_at' => now(),
        ]);

        $this->artisan('app:import-avatars', [
            'source' => sys_get_temp_dir().'/nonexistent-media',
        ])->assertSuccessful();

        $user->refresh();
        $this->assertEquals('avatars/existing.jpg', $user->avatar_path);
    }
}
