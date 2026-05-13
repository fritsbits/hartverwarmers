<?php

namespace App\Listeners;

use App\Events\CommentPosted;
use App\Models\Fiche;
use App\Models\PendingNotification;

class SendFicheCommentNotification
{
    public function handle(CommentPosted $event): void
    {
        $comment = $event->comment;
        $fiche = $comment->commentable;

        if (! $fiche instanceof Fiche) {
            return;
        }

        $owner = $fiche->user;

        if ($owner === null) {
            return;
        }

        if ($fiche->user_id === $comment->user_id) {
            return;
        }

        if ($owner->notification_frequency === 'never') {
            return;
        }

        $fiche->loadMissing('initiative');

        PendingNotification::create([
            'user_id' => $owner->id,
            'type' => 'fiche_comment',
            'fiche_id' => $fiche->id,
            'payload' => [
                'comment_id' => $comment->id,
                'body_excerpt' => $this->safeExcerpt($comment->body),
                'commenter_name' => $comment->user?->full_name ?? 'Anoniem',
                'comment_url' => route('fiches.show', [$fiche->initiative, $fiche])
                    .'?reply='.$comment->id
                    .'#comment-'.$comment->id,
            ],
        ]);
    }

    private function safeExcerpt(string $body): string
    {
        $truncated = mb_substr($body, 0, 200);

        return preg_replace('/([\\\\`*_{}\[\]()#+\-!>|~])/u', '\\\\$1', $truncated);
    }
}
