<?php

namespace App\Notifications;

use App\Models\Fiche;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
