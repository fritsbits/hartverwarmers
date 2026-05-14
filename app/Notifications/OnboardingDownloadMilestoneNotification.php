<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class OnboardingDownloadMilestoneNotification extends BaseMailNotification
{
    public function __construct(public int $downloadCount) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Heb jij ook een activiteit die werkt voor je bewoners?')
            ->markdown('emails.onboarding-contribute-invitation', [
                'notifiable' => $notifiable,
                'downloadCount' => $this->downloadCount,
            ]);
    }
}
