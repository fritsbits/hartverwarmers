<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class OnboardingMilestone50BookmarksNotification extends BaseMailNotification
{
    public function __construct(public int $bookmarkCount) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('50 bewaarders — jij bent een vaste waarde op Hartverwarmers')
            ->markdown('emails.onboarding-milestone-50-bookmarks', [
                'notifiable' => $notifiable,
                'bookmarkCount' => $this->bookmarkCount,
            ]);
    }
}
