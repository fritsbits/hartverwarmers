<?php

namespace Tests\Feature\Services\ContributorAnniversary;

use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Like;
use App\Models\User;
use App\Services\ContributorAnniversary\Composer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComposerTest extends TestCase
{
    use RefreshDatabase;

    public function test_total_fiches_counts_only_published(): void
    {
        $user = User::factory()->create();
        Fiche::factory()->for($user)->count(3)->create(['published' => true]);
        Fiche::factory()->for($user)->create(['published' => false]);

        $payload = (new Composer)->compose($user);

        $this->assertSame(3, $payload->totalFiches);
    }

    public function test_total_bookmarks_sums_across_users_fiches(): void
    {
        $user = User::factory()->create();
        $fiche1 = Fiche::factory()->for($user)->create(['published' => true]);
        $fiche2 = Fiche::factory()->for($user)->create(['published' => true]);
        Like::factory()->bookmark()->count(3)->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche1->id,
        ]);
        Like::factory()->bookmark()->count(5)->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche2->id,
        ]);

        $payload = (new Composer)->compose($user);

        $this->assertSame(8, $payload->totalBookmarks);
    }

    public function test_total_comments_sums_across_users_fiches(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->for($user)->create(['published' => true]);
        Comment::factory()->count(4)->create([
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
        ]);

        $payload = (new Composer)->compose($user);

        $this->assertSame(4, $payload->totalComments);
    }

    public function test_spotlight_is_most_bookmarked_fiche(): void
    {
        $user = User::factory()->create();
        $low = Fiche::factory()->for($user)->create(['published' => true, 'title' => 'Lage']);
        $high = Fiche::factory()->for($user)->create(['published' => true, 'title' => 'Hoge']);
        Like::factory()->bookmark()->count(2)->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $low->id,
        ]);
        Like::factory()->bookmark()->count(7)->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $high->id,
        ]);

        $payload = (new Composer)->compose($user);

        $this->assertNotNull($payload->spotlightFiche);
        $this->assertSame($high->id, $payload->spotlightFiche->id);
        $this->assertSame(7, $payload->spotlightBookmarkCount);
    }

    public function test_spotlight_ties_break_to_most_recent(): void
    {
        $user = User::factory()->create();
        $older = Fiche::factory()->for($user)->create(['published' => true, 'created_at' => now()->subDays(10)]);
        $newer = Fiche::factory()->for($user)->create(['published' => true, 'created_at' => now()->subDays(2)]);
        Like::factory()->bookmark()->count(3)->create(['likeable_type' => Fiche::class, 'likeable_id' => $older->id]);
        Like::factory()->bookmark()->count(3)->create(['likeable_type' => Fiche::class, 'likeable_id' => $newer->id]);

        $payload = (new Composer)->compose($user);

        $this->assertSame($newer->id, $payload->spotlightFiche->id);
    }

    public function test_spotlight_is_null_when_no_bookmarks_anywhere(): void
    {
        $user = User::factory()->create();
        Fiche::factory()->for($user)->count(2)->create(['published' => true]);

        $payload = (new Composer)->compose($user);

        $this->assertNull($payload->spotlightFiche);
        $this->assertNull($payload->spotlightBookmarkCount);
    }

    public function test_spotlight_ignores_unpublished_fiches(): void
    {
        $user = User::factory()->create();
        $unpublished = Fiche::factory()->for($user)->create(['published' => false]);
        $published = Fiche::factory()->for($user)->create(['published' => true]);
        Like::factory()->bookmark()->count(10)->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $unpublished->id,
        ]);
        Like::factory()->bookmark()->count(1)->create([
            'likeable_type' => Fiche::class,
            'likeable_id' => $published->id,
        ]);

        $payload = (new Composer)->compose($user);

        $this->assertSame($published->id, $payload->spotlightFiche->id);
    }
}
