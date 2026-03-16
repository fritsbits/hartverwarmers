<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Like;
use App\Models\User;
use App\Models\UserInteraction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeStubUsersTest extends TestCase
{
    use RefreshDatabase;

    private function createStubAndRealUser(string $firstName = 'Jarne', string $lastName = 'Hennebel'): array
    {
        $stub = User::factory()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower("{$firstName}-{$lastName}@import.hartverwarmers.be"),
        ]);

        $real = User::factory()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => 'jarne.hennebel@kei.be',
        ]);

        return [$stub, $real];
    }

    public function test_moves_fiches_from_stub_to_real_user(): void
    {
        [$stub, $real] = $this->createStubAndRealUser();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $stub->id, 'initiative_id' => $initiative->id]);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertEquals($real->id, $fiche->fresh()->user_id);
    }

    public function test_moves_comments_from_stub_to_real_user(): void
    {
        [$stub, $real] = $this->createStubAndRealUser();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
        $comment = Comment::factory()->create(['user_id' => $stub->id, 'commentable_type' => Fiche::class, 'commentable_id' => $fiche->id]);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertEquals($real->id, $comment->fresh()->user_id);
    }

    public function test_moves_likes_from_stub_to_real_user(): void
    {
        [$stub, $real] = $this->createStubAndRealUser();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
        $like = Like::create(['user_id' => $stub->id, 'likeable_type' => Fiche::class, 'likeable_id' => $fiche->id, 'type' => 'kudos', 'count' => 3]);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertEquals($real->id, $like->fresh()->user_id);
    }

    public function test_merges_duplicate_likes_by_summing_count(): void
    {
        [$stub, $real] = $this->createStubAndRealUser();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Like::create(['user_id' => $stub->id, 'likeable_type' => Fiche::class, 'likeable_id' => $fiche->id, 'type' => 'kudos', 'count' => 5]);
        $realLike = Like::create(['user_id' => $real->id, 'likeable_type' => Fiche::class, 'likeable_id' => $fiche->id, 'type' => 'kudos', 'count' => 3]);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertEquals(8, $realLike->fresh()->count);
        $this->assertDatabaseMissing('likes', ['user_id' => $stub->id]);
    }

    public function test_moves_user_interactions_from_stub_to_real(): void
    {
        [$stub, $real] = $this->createStubAndRealUser();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
        UserInteraction::create(['user_id' => $stub->id, 'interactable_type' => Fiche::class, 'interactable_id' => $fiche->id, 'type' => 'view']);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertDatabaseHas('user_interactions', ['user_id' => $real->id, 'type' => 'view']);
        $this->assertDatabaseMissing('user_interactions', ['user_id' => $stub->id]);
    }

    public function test_moves_initiative_created_by_from_stub_to_real(): void
    {
        [$stub, $real] = $this->createStubAndRealUser();
        $initiative = Initiative::factory()->published()->create(['created_by' => $stub->id]);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertEquals($real->id, $initiative->fresh()->created_by);
    }

    public function test_copies_profile_fields_from_stub_when_real_is_empty(): void
    {
        $stub = User::factory()->create([
            'first_name' => 'An',
            'last_name' => 'Test',
            'email' => 'an-test@import.hartverwarmers.be',
            'organisation' => 'WZC Zonneveld',
            'function_title' => 'Animator',
            'bio' => 'Een bio.',
        ]);

        $real = User::factory()->create([
            'first_name' => 'An',
            'last_name' => 'Test',
            'email' => 'an@test.be',
            'organisation' => null,
            'function_title' => null,
            'bio' => null,
        ]);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $real->refresh();
        $this->assertEquals('WZC Zonneveld', $real->organisation);
        $this->assertEquals('Animator', $real->function_title);
        $this->assertEquals('Een bio.', $real->bio);
    }

    public function test_does_not_overwrite_existing_profile_fields(): void
    {
        $stub = User::factory()->create([
            'first_name' => 'An',
            'last_name' => 'Test',
            'email' => 'an-test@import.hartverwarmers.be',
            'organisation' => 'Old Org',
        ]);

        $real = User::factory()->create([
            'first_name' => 'An',
            'last_name' => 'Test',
            'email' => 'an@test.be',
            'organisation' => 'Current Org',
        ]);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertEquals('Current Org', $real->fresh()->organisation);
    }

    public function test_soft_deletes_stub_user(): void
    {
        [$stub, $real] = $this->createStubAndRealUser();

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertSoftDeleted('users', ['id' => $stub->id]);
        $this->assertNull(User::find($real->id)->deleted_at);
    }

    public function test_dry_run_does_not_change_data(): void
    {
        [$stub, $real] = $this->createStubAndRealUser();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['user_id' => $stub->id, 'initiative_id' => $initiative->id]);

        $this->artisan('app:merge-stub-users', ['--dry-run' => true])->assertSuccessful();

        $this->assertEquals($stub->id, $fiche->fresh()->user_id);
        $this->assertNull(User::find($stub->id)->deleted_at);
    }

    public function test_skips_stub_without_matching_real_user(): void
    {
        User::factory()->create([
            'first_name' => 'Orphan',
            'last_name' => 'Stub',
            'email' => 'orphan-stub@import.hartverwarmers.be',
        ]);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'orphan-stub@import.hartverwarmers.be', 'deleted_at' => null]);
    }

    public function test_moves_file_uploads_from_stub_to_real(): void
    {
        [$stub, $real] = $this->createStubAndRealUser();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
        $file = \App\Models\File::factory()->create(['fiche_id' => $fiche->id]);
        \App\Models\FileUpload::factory()->create([
            'user_id' => $stub->id,
            'file_id' => $file->id,
        ]);

        $this->artisan('app:merge-stub-users')->assertSuccessful();

        $this->assertDatabaseHas('file_uploads', ['user_id' => $real->id]);
        $this->assertDatabaseMissing('file_uploads', ['user_id' => $stub->id]);
    }
}
