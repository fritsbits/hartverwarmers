<?php

namespace App\Listeners;

use App\Events\CommentPosted;
use App\Models\Fiche;
use App\Notifications\FicheCommentNotification;

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

        $owner->notify(new FicheCommentNotification($comment));
    }
}
