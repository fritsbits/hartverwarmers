<?php

namespace App\Notifications;

use App\Models\Fiche;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OnboardingCuratedActivitiesNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Handmatig gecureerde fiche-IDs. Pas aan naar actuele topfiches.
     *
     * @var array<int>
     */
    private const CURATED_FICHE_IDS = [56, 61, 320];

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $fiches = Fiche::published()
            ->with(['initiative', 'user'])
            ->whereIn('id', self::CURATED_FICHE_IDS)
            ->get();

        return (new MailMessage)
            ->subject("Activiteiten die andere animatoren al gebruiken, {$notifiable->first_name}")
            ->markdown('emails.onboarding-curated-activities', [
                'notifiable' => $notifiable,
                'fiches' => $fiches,
            ]);
    }
}
