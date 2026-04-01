<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OnboardingContributeInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Jouw ervaring is waardevol voor andere WZC's")
            ->greeting("Hoi {$notifiable->first_name}!")
            ->line('Je bent al een tijdje lid van Hartverwarmers. Andere animatoren delen elke week nieuwe activiteiten — en jij hebt vast ook dingen die werken.')
            ->line('Een fiche delen hoeft niet perfect te zijn. Een paar zinnen over wat je doet, voor wie het werkt, en hoe je het aanpakt. Dat is genoeg.')
            ->action('Deel je eerste activiteit', url('/fiches/nieuw'))
            ->line('Andere teams in Vlaanderen zullen je er dankbaar voor zijn.')
            ->salutation("Warme groet,\nHet Hartverwarmers-team");
    }
}
