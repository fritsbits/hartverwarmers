<?php

namespace App\Notifications;

use App\Models\Fiche;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class OnboardingDownloadFollowupNotification extends Notification implements ShouldQueue
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
            ->subject("Heb je '{$this->fiche->title}' al uitgeprobeerd?")
            ->markdown('emails.onboarding-download-followup', [
                'notifiable' => $notifiable,
                'fiche' => $this->fiche,
            ]);
    }
}
