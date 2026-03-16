<?php

namespace Tests\Feature\Files;

use App\Livewire\AvatarUpload;
use App\Models\User;
use App\Services\AvatarThumbnailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class AvatarUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $mock = Mockery::mock(AvatarThumbnailService::class);
        $mock->shouldReceive('generate')->andReturn('avatars/fake-thumb.webp');
        $mock->shouldReceive('delete')->andReturnNull();

        $this->app->instance(AvatarThumbnailService::class, $mock);
    }

    public function test_avatar_upload_component_renders_on_profile_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.show'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(AvatarUpload::class);
    }

    public function test_user_can_upload_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AvatarUpload::class)
            ->set('photo', UploadedFile::fake()->image('avatar.jpg', 200, 200))
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_user_can_delete_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        // Upload first
        Livewire::actingAs($user)
            ->test(AvatarUpload::class)
            ->set('photo', UploadedFile::fake()->image('avatar.jpg', 200, 200));

        $user->refresh();
        $avatarPath = $user->avatar_path;
        $this->assertNotNull($avatarPath);

        // Then delete
        Livewire::actingAs($user)
            ->test(AvatarUpload::class)
            ->call('deleteAvatar');

        $user->refresh();
        $this->assertNull($user->avatar_path);
        Storage::disk('public')->assertMissing($avatarPath);
    }

    public function test_avatar_upload_validates_file_type(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AvatarUpload::class)
            ->set('photo', UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'))
            ->assertHasErrors('photo');
    }

    public function test_avatar_upload_validates_file_size(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AvatarUpload::class)
            ->set('photo', UploadedFile::fake()->image('large.jpg')->size(3000))
            ->assertHasErrors('photo');
    }

    public function test_uploading_new_avatar_deletes_old_one(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        // Upload first avatar
        Livewire::actingAs($user)
            ->test(AvatarUpload::class)
            ->set('photo', UploadedFile::fake()->image('first.jpg', 200, 200));

        $user->refresh();
        $firstPath = $user->avatar_path;

        // Upload second avatar
        Livewire::actingAs($user)
            ->test(AvatarUpload::class)
            ->set('photo', UploadedFile::fake()->image('second.jpg', 200, 200));

        $user->refresh();
        $this->assertNotEquals($firstPath, $user->avatar_path);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_thumbnail_service_is_called_on_upload(): void
    {
        Storage::fake('public');

        $mock = Mockery::mock(AvatarThumbnailService::class);
        $mock->shouldReceive('generate')->once()->andReturn('avatars/fake-thumb.webp');
        $this->app->instance(AvatarThumbnailService::class, $mock);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AvatarUpload::class)
            ->set('photo', UploadedFile::fake()->image('avatar.jpg', 200, 200))
            ->assertHasNoErrors();
    }

    public function test_thumbnail_service_is_called_on_delete(): void
    {
        Storage::fake('public');

        $mock = Mockery::mock(AvatarThumbnailService::class);
        $mock->shouldReceive('generate')->once()->andReturn('avatars/fake-thumb.webp');
        $mock->shouldReceive('delete')->once();
        $this->app->instance(AvatarThumbnailService::class, $mock);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AvatarUpload::class)
            ->set('photo', UploadedFile::fake()->image('avatar.jpg', 200, 200));

        $user->refresh();

        Livewire::actingAs($user)
            ->test(AvatarUpload::class)
            ->call('deleteAvatar');
    }

    public function test_avatar_url_returns_thumbnail_when_exists(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['avatar_path' => 'avatars/test.jpg']);

        // Without thumbnail, falls back to original
        Storage::disk('public')->put('avatars/test.jpg', 'original');
        $this->assertEquals(Storage::url('avatars/test.jpg'), $user->avatarUrl());

        // With thumbnail, uses thumbnail
        Storage::disk('public')->put('avatars/test-thumb.webp', 'thumb');
        $this->assertEquals(Storage::url('avatars/test-thumb.webp'), $user->avatarUrl());
    }

    public function test_avatar_url_returns_null_when_no_avatar(): void
    {
        $user = User::factory()->create(['avatar_path' => null]);

        $this->assertNull($user->avatarUrl());
    }
}
