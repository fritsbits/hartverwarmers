<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OnboardingMilestone10BookmarksNotification extends Notification implements ShouldQueue
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
            ->subject("{$this->bookmarkCount} mensen bewaarden jouw activiteiten — bedankt!")
            ->greeting("Hoi {$notifiable->first_name}!")
            ->line("Je activiteiten werden al **{$this->bookmarkCount} keer** bewaard door andere animatoren. Ze gebruiken jouw werk om het leven van bewoners te verrijken.")
            ->action('Bekijk je bijdragen', route('contributors.index'))
            ->line('Wil je nog een activiteit delen? Elke fiche helpt iemand verder.')
            ->salutation("Warme groet,\nHet Hartverwarmers-team");
    }
}
