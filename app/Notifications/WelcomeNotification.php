<?php

namespace App\Notifications;

use App\Models\Fiche;
use Illuminate\Notifications\Messages\MailMessage;

class WelcomeNotification extends BaseMailNotification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ficheCount = Fiche::published()->count();

        return (new MailMessage)
            ->subject("Welkom bij Hartverwarmers, {$notifiable->first_name}!")
            ->markdown('emails.welcome', [
                'notifiable' => $notifiable,
                'ficheCount' => $ficheCount,
            ]);
    }
}
