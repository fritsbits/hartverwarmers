<?php

namespace App\Livewire;

use App\Models\Comment;
use App\Models\Fiche;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class FicheComments extends Component
{
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

    public function render()
    {
        return view('livewire.fiche-comments');
    }
}
