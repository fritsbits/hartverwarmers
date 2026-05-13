<?php

namespace App\Notifications;

use App\Models\Fiche;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;

class OnboardingFirstBookmarkNotification extends BaseMailNotification
{
    use SerializesModels;

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
            ->markdown('emails.onboarding-first-bookmark', [
                'notifiable' => $notifiable,
                'fiche' => $this->fiche,
            ]);
    }
}
