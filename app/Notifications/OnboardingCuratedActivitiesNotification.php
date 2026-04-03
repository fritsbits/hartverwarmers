<?php

namespace App\Notifications;

use App\Models\Fiche;
use Illuminate\Notifications\Messages\MailMessage;

class OnboardingCuratedActivitiesNotification extends BaseMailNotification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $fiches = Fiche::published()
            ->where('has_diamond', true)
            ->with(['initiative', 'user'])
            ->inRandomOrder()
            ->limit(5)
            ->get();

        return (new MailMessage)
            ->subject("De diamantjes van Hartverwarmers — voor jou uitgekozen, {$notifiable->first_name}")
            ->markdown('emails.onboarding-curated-activities', [
                'notifiable' => $notifiable,
                'fiches' => $fiches,
            ]);
    }
}
