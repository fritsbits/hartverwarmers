<?php

namespace App\Notifications;

use App\Models\Fiche;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class OnboardingFirstBookmarkNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Fiche $fiche) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->fiche->loadMissing('initiative');

        return (new MailMessage)
            ->subject("Iemand bewaarde jouw fiche '{$this->fiche->title}' ♥")
            ->greeting("Hoi {$notifiable->first_name}!")
            ->line("Goed nieuws: iemand heeft je fiche **{$this->fiche->title}** bewaard. Ze willen het gebruiken met hun bewoners.")
            ->action('Bekijk je fiche', route('fiches.show', [$this->fiche->initiative, $this->fiche]))
            ->line('Bedankt dat je deelt. Dit is precies waarom Hartverwarmers bestaat.')
            ->salutation("Warme groet,\nHet Hartverwarmers-team");
    }
}
