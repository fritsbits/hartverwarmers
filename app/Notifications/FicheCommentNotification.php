<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Fiche;
use Illuminate\Notifications\Messages\MailMessage;

class FicheCommentNotification extends BaseMailNotification
{
    public function __construct(public Comment $comment) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var Fiche $fiche */
        $fiche = $this->comment->commentable;
        $fiche->loadMissing('initiative');
        $commenter = $this->comment->user;

        $url = route('fiches.show', [$fiche->initiative, $fiche])
            .'?reply='.$this->comment->id
            .'#comment-'.$this->comment->id;

        return (new MailMessage)
            ->subject("Nieuwe reactie op je fiche: {$fiche->title}")
            ->markdown('emails.fiche-comment', [
                'notifiable' => $notifiable,
                'commenter' => $commenter,
                'fiche' => $fiche,
                'comment' => $this->comment,
                'url' => $url,
            ]);
    }
}
