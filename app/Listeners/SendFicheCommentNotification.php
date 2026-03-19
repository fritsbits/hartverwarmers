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

        if ($fiche->user === null) {
            return;
        }

        if ($fiche->user_id === $comment->user_id) {
            return;
        }

        if (! $fiche->user->notify_on_fiche_comments) {
            return;
        }

        $fiche->user->notify(new FicheCommentNotification($comment));
    }
}
