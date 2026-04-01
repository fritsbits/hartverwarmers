<?php

namespace App\Notifications;

use App\Models\Fiche;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OnboardingTopFiveNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $topFiches = Fiche::published()
            ->with('initiative')
            ->withCount(['likes as bookmarks_count' => fn ($q) => $q->where('type', 'bookmark')])
            ->orderByDesc('bookmarks_count')
            ->limit(5)
            ->get();

        return (new MailMessage)
            ->subject('De 5 meest bewaarde activiteiten op Hartverwarmers')
            ->markdown('emails.onboarding-top-five', [
                'notifiable' => $notifiable,
                'topFiches' => $topFiches,
            ]);
    }
}
