<?php

namespace App\Notifications;

use App\Support\Reactivation\ReactivationContent;
use Illuminate\Notifications\Messages\MailMessage;

class ReactivationNotification extends BaseMailNotification
{
    public function __construct(public ReactivationContent $content) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->content->fichesCount." activiteiten van collega's, klaar voor je bewoners")
            ->view('emails.reactivation', [
                'notifiable' => $notifiable,
                'content' => $this->content,
            ]);
    }
}
