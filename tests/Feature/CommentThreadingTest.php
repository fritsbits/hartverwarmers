<?php

namespace Tests\Feature;

use App\Livewire\FicheComments;
use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Like;
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

    public function test_guest_sees_inline_comment_form(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Livewire::test(FicheComments::class, ['fiche' => $fiche])
            ->assertSee('Wees de eerste die reageert!')
            ->assertSee('Al een account?')
            ->assertSee('Nog even je naam erbij');
    }

    public function test_guest_can_create_account_and_comment(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Livewire::test(FicheComments::class, ['fiche' => $fiche])
            ->set('guestName', 'Marie Janssen')
            ->set('guestEmail', 'marie@example.com')
            ->set('guestBody', 'Geweldige fiche, bedankt!')
            ->set('guestTerms', true)
            ->call('addGuestComment');

        $this->assertDatabaseHas('users', [
            'first_name' => 'Marie',
            'last_name' => 'Janssen',
            'email' => 'marie@example.com',
        ]);

        $user = User::where('email', 'marie@example.com')->first();
        $this->assertNotNull($user->terms_accepted_at);

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'body' => 'Geweldige fiche, bedankt!',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_comment_rejects_existing_email(): void
    {
        $existing = User::factory()->create(['email' => 'taken@example.com']);
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Livewire::test(FicheComments::class, ['fiche' => $fiche])
            ->set('guestName', 'Jan Peeters')
            ->set('guestEmail', 'taken@example.com')
            ->set('guestBody', 'Leuke fiche!')
            ->set('guestTerms', true)
            ->call('addGuestComment')
            ->assertHasErrors('guestEmail');
    }

    public function test_guest_comment_requires_body(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Livewire::test(FicheComments::class, ['fiche' => $fiche])
            ->set('guestBody', '')
            ->call('addGuestComment')
            ->assertHasErrors('guestBody');
    }

    public function test_guest_comment_requires_identity_fields(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        Livewire::test(FicheComments::class, ['fiche' => $fiche])
            ->set('guestBody', 'Een reactie')
            ->set('guestName', '')
            ->set('guestEmail', '')
            ->set('guestTerms', false)
            ->call('addGuestComment')
            ->assertHasErrors(['guestName', 'guestEmail', 'guestTerms']);
    }

    public function test_guest_session_kudos_merged_on_account_creation(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        // Simulate session kudos
        $sessionId = session()->getId();
        Like::create([
            'user_id' => null,
            'session_id' => $sessionId,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 5,
        ]);

        Livewire::test(FicheComments::class, ['fiche' => $fiche])
            ->set('guestName', 'Sofie De Mol')
            ->set('guestEmail', 'sofie@example.com')
            ->set('guestBody', 'Heel nuttig!')
            ->set('guestTerms', true)
            ->call('addGuestComment');

        $user = User::where('email', 'sofie@example.com')->first();

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'session_id' => null,
            'likeable_type' => Fiche::class,
            'likeable_id' => $fiche->id,
            'type' => 'kudos',
            'count' => 5,
        ]);
    }

    public function test_guest_can_reply_with_inline_account_creation(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
        $parent = Comment::factory()->create([
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
        ]);

        Livewire::test(FicheComments::class, ['fiche' => $fiche])
            ->call('startReply', $parent->id)
            ->set('replyBody', 'Bedankt voor de tip!')
            ->set('guestName', 'Eva Peeters')
            ->set('guestEmail', 'eva@example.com')
            ->set('guestTerms', true)
            ->call('addGuestReply');

        $user = User::where('email', 'eva@example.com')->first();
        $this->assertNotNull($user);

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'body' => 'Bedankt voor de tip!',
            'parent_id' => $parent->id,
        ]);

        $this->assertAuthenticatedAs($user);
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
