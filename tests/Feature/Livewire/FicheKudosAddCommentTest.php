<?php

namespace Tests\Feature\Livewire;

use App\Events\CommentPosted;
use App\Livewire\FicheKudos;
use App\Models\Fiche;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class FicheKudosAddCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_add_comment(): void
    {
        Event::fake([CommentPosted::class]);

        $user = User::factory()->create();
        $fiche = Fiche::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->set('body', 'wat een mooie activiteit, dank je wel!')
            ->call('addComment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $fiche->id,
            'body' => 'wat een mooie activiteit, dank je wel!',
            'parent_id' => null,
        ]);

        Event::assertDispatched(CommentPosted::class, fn (CommentPosted $e) => $e->comment->user_id === $user->id);
    }

    public function test_add_comment_resets_body_after_success(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->set('body', 'merci voor het delen')
            ->call('addComment')
            ->assertSet('body', '');
    }

    public function test_add_comment_dispatches_browser_event_for_takeover_state_change(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->set('body', 'mooi werk')
            ->call('addComment')
            ->assertDispatched('comment-added');
    }

    public function test_unauthenticated_user_cannot_add_comment(): void
    {
        $fiche = Fiche::factory()->create();

        Livewire::test(FicheKudos::class, ['fiche' => $fiche])
            ->set('body', 'guest poging')
            ->call('addComment');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_user_cannot_comment_on_own_fiche(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->set('body', 'eigen fiche reactie')
            ->call('addComment');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_add_comment_validates_required_body(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->set('body', '')
            ->call('addComment')
            ->assertHasErrors(['body' => 'required']);

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_add_comment_validates_max_length(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->create();
        $tooLong = str_repeat('a', 1001);

        Livewire::actingAs($user)
            ->test(FicheKudos::class, ['fiche' => $fiche])
            ->set('body', $tooLong)
            ->call('addComment')
            ->assertHasErrors(['body' => 'max']);

        $this->assertDatabaseCount('comments', 0);
    }
}
