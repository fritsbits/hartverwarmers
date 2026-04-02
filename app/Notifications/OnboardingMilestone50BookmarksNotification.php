<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OnboardingMilestone50BookmarksNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $bookmarkCount) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('50 bewaarders — jij bent een vaste waarde op Hartverwarmers')
            ->greeting("Hoi {$notifiable->first_name}!")
            ->line("Wauw — je activiteiten werden intussen al **{$this->bookmarkCount} keer** bewaard. Dat is geen toeval: je deelt dingen die écht werken.")
            ->action('Bekijk je bijdragen', route('contributors.index'))
            ->line('Benieuwd welke andere activiteiten zo populair zijn? Bekijk onze diamantjes — de beste fiches van de community, met de hand uitgekozen.')
            ->action('Bekijk de diamantjes', url('/diamantjes'))
            ->salutation("Warme groet,\nHet Hartverwarmers-team");
    }
}
