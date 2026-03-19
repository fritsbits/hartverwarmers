<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Fiche;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FicheCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

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

        return (new MailMessage)
            ->subject("Nieuwe reactie op je fiche: {$fiche->title}")
            ->greeting("Hoi {$notifiable->first_name}!")
            ->line("{$commenter->first_name} heeft een reactie geplaatst op je fiche.")
            ->line('Reageer terug en hou het gesprek gaande.')
            ->action('Bekijk de reactie', route('fiches.show', [$fiche->initiative, $fiche]))
            ->salutation("Warme groet,\nHet Hartverwarmers-team");
    }
}
