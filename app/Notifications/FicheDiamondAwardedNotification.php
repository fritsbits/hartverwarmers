<?php

namespace App\Notifications;

use App\Models\Fiche;
use Illuminate\Notifications\Messages\MailMessage;

class FicheDiamondAwardedNotification extends BaseMailNotification
{
    public function __construct(public Fiche $fiche) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->fiche->loadMissing('initiative');

        return (new MailMessage)
            ->subject("Jouw fiche '{$this->fiche->title}' werd een diamantje ✨")
            ->markdown('emails.diamond-awarded', [
                'notifiable' => $notifiable,
                'fiche' => $this->fiche,
            ]);
    }
}
