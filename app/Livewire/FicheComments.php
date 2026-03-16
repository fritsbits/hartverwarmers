<?php

namespace App\Livewire;

use App\Livewire\Concerns\CreatesGuestAccount;
use App\Models\Comment;
use App\Models\Fiche;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class FicheComments extends Component
{
    use CreatesGuestAccount;

    public Fiche $fiche;

    #[Validate('required|string|max:1000', message: [
        'body.required' => 'Schrijf een reactie.',
        'body.max' => 'Je reactie mag maximaal 1000 tekens bevatten.',
    ])]
    public string $body = '';

    public ?int $replyingTo = null;

    #[Validate('required|string|max:1000', message: [
        'replyBody.required' => 'Schrijf een reactie.',
        'replyBody.max' => 'Je reactie mag maximaal 1000 tekens bevatten.',
    ])]
    public string $replyBody = '';

    public string $guestBody = '';

    public function addComment(): void
    {
        $this->validate(['body' => 'required|string|max:1000']);

        Comment::create([
            'body' => $this->body,
            'user_id' => auth()->id(),
            'commentable_type' => Fiche::class,
            'commentable_id' => $this->fiche->id,
            'parent_id' => null,
        ]);

        $this->reset('body');
        unset($this->comments);
    }

    public function addGuestComment(): void
    {
        $this->validate([
            'guestBody' => ['required', 'string', 'max:1000'],
        ], [
            'guestBody.required' => 'Schrijf een reactie.',
            'guestBody.max' => 'Je reactie mag maximaal 1000 tekens bevatten.',
        ]);

        $this->validateGuestIdentity();

        $user = $this->createGuestUser();

        Comment::create([
            'body' => $this->guestBody,
            'user_id' => $user->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $this->fiche->id,
            'parent_id' => null,
        ]);

        $this->reset('guestBody');
        unset($this->comments);

        $this->dispatch('guest-welcome', name: $user->first_name);
    }

    public function addGuestReply(): void
    {
        $this->validate([
            'replyBody' => 'required|string|max:1000',
        ], [
            'replyBody.required' => 'Schrijf een reactie.',
            'replyBody.max' => 'Je reactie mag maximaal 1000 tekens bevatten.',
        ]);

        $this->validateGuestIdentity();

        $parent = Comment::findOrFail($this->replyingTo);
        $user = $this->createGuestUser();

        Comment::create([
            'body' => $this->replyBody,
            'user_id' => $user->id,
            'commentable_type' => Fiche::class,
            'commentable_id' => $this->fiche->id,
            'parent_id' => $parent->id,
        ]);

        $this->replyingTo = null;
        $this->replyBody = '';
        unset($this->comments);

        $this->dispatch('guest-welcome', name: $user->first_name);
    }

    public function startReply(int $commentId): void
    {
        $this->replyingTo = $commentId;
        $this->replyBody = '';
    }

    public function cancelReply(): void
    {
        $this->replyingTo = null;
        $this->replyBody = '';
    }

    public function addReply(): void
    {
        $this->validate(['replyBody' => 'required|string|max:1000']);

        $parent = Comment::findOrFail($this->replyingTo);

        Comment::create([
            'body' => $this->replyBody,
            'user_id' => auth()->id(),
            'commentable_type' => Fiche::class,
            'commentable_id' => $this->fiche->id,
            'parent_id' => $parent->id,
        ]);

        $this->replyingTo = null;
        $this->replyBody = '';
        unset($this->comments);
    }

    #[Computed]
    public function comments()
    {
        return $this->fiche->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->latest()
            ->get();
    }

    #[Computed]
    public function commentCount(): int
    {
        return $this->comments->count()
            + $this->comments->sum(fn ($c) => $c->replies->count());
    }

    #[Computed]
    public function isAuthor(): bool
    {
        return auth()->id() === $this->fiche->user_id;
    }

    public function render()
    {
        return view('livewire.fiche-comments');
    }
}
