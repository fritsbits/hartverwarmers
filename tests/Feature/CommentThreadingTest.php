<?php

namespace Tests\Feature;

use App\Livewire\FicheComments;
use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CommentThreadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_comment(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Livewire::actingAs($user)
            ->test(FicheComments::class, ['fiche' => $fiche])
            ->set('body', 'Geweldige fiche!')
            ->call('addComment');

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'body' => 'Geweldige fiche!',
            'parent_id' => null,
        ]);
    }

    public function test_user_can_reply_to_comment(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
        $parent = Comment::factory()->create([
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
        ]);

        Livewire::actingAs($user)
            ->test(FicheComments::class, ['fiche' => $fiche])
            ->call('startReply', $parent->id)
            ->set('replyBody', 'Bedankt voor de tip!')
            ->call('addReply');

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'body' => 'Bedankt voor de tip!',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_comments_display_with_replies(): void
    {
        $user = User::factory()->create(['first_name' => 'Jan', 'last_name' => 'De Vries']);
        $replier = User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Janssen']);
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $parent = Comment::factory()->create([
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'user_id' => $user->id,
            'body' => 'Top fiche!',
        ]);
        Comment::factory()->create([
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'user_id' => $replier->id,
            'body' => 'Helemaal mee eens!',
            'parent_id' => $parent->id,
        ]);

        Livewire::test(FicheComments::class, ['fiche' => $fiche])
            ->assertSee('Top fiche!')
            ->assertSee('Helemaal mee eens!')
            ->assertSee('Jan De Vries')
            ->assertSee('Marie Janssen');
    }

    public function test_cascade_delete_removes_replies(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $parent = Comment::factory()->create([
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'body' => 'Parent comment',
        ]);
        $reply = Comment::factory()->create([
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'body' => 'Reply comment',
            'parent_id' => $parent->id,
        ]);

        $parent->forceDelete();

        $this->assertDatabaseMissing('comments', ['id' => $reply->id]);
    }

    public function test_comment_via_http_stores_parent_id(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();
        $parent = Comment::factory()->create([
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
        ]);

        $response = $this->actingAs($user)->post(route('fiches.comment', $fiche), [
            'body' => 'Reply via HTTP',
            'parent_id' => $parent->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'body' => 'Reply via HTTP',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_comment_validates_parent_id_exists(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        $response = $this->actingAs($user)->post(route('fiches.comment', $fiche), [
            'body' => 'Reply to nonexistent',
            'parent_id' => 99999,
        ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function test_cancel_reply_resets_state(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
        $parent = Comment::factory()->create([
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
        ]);

        Livewire::actingAs($user)
            ->test(FicheComments::class, ['fiche' => $fiche])
            ->call('startReply', $parent->id)
            ->assertSet('replyingTo', $parent->id)
            ->call('cancelReply')
            ->assertSet('replyingTo', null)
            ->assertSet('replyBody', '');
    }

    public function test_comment_count_includes_replies(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        $parent = Comment::factory()->create([
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
        ]);
        Comment::factory()->create([
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'parent_id' => $parent->id,
        ]);

        $component = Livewire::test(FicheComments::class, ['fiche' => $fiche]);

        $this->assertEquals(2, $component->get('commentCount'));
    }

    public function test_guest_cannot_add_comment_via_livewire(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Livewire::test(FicheComments::class, ['fiche' => $fiche])
            ->assertDontSee('Deel je ervaring')
            ->assertSee('Log in');
    }

    public function test_body_validation_required(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Livewire::actingAs($user)
            ->test(FicheComments::class, ['fiche' => $fiche])
            ->set('body', '')
            ->call('addComment')
            ->assertHasErrors('body');
    }
}
