<?php

namespace App\Notifications;

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
        return (new MailMessage)
            ->subject("Welkom bij Hartverwarmers, {$notifiable->first_name}!")
            ->greeting("Hoi {$notifiable->first_name}!")
            ->line('Fijn dat je erbij bent! Collega\'s uit heel Vlaanderen delen hier deugddoende initiatieven voor ouderen in de zorg — van kleine dagelijkse rituelen tot uitgewerkte activiteiten.')
            ->line('Blader rustig rond, bewaar wat je aanspreekt met het hartje, en deel gerust [je eigen fiche]('.url('/fiches/nieuw').') wanneer je er klaar voor bent.')
            ->action('Ontdek initiatieven', url('/initiatieven'))
            ->line('Benieuwd wie er achter Hartverwarmers zit? [Lees meer over ons]('.url('/over-ons').').')
            ->line('We zijn blij dat je erbij bent.')
            ->salutation("Warme groet,\nHet Hartverwarmers-team");
    }
}
