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
        $recentFiches = Fiche::published()
            ->with('initiative')
            ->withCount(['likes as bookmarks_count' => fn ($q) => $q->where('type', 'bookmark')->where('created_at', '>=', now()->subDays(30))])
            ->having('bookmarks_count', '>', 0)
            ->orderByDesc('bookmarks_count')
            ->limit(3)
            ->get();

        $allTimeFiches = Fiche::published()
            ->with('initiative')
            ->withCount(['likes as bookmarks_count' => fn ($q) => $q->where('type', 'bookmark')])
            ->whereNotIn('id', $recentFiches->pluck('id'))
            ->orderByDesc('bookmarks_count')
            ->limit(3)
            ->get();

        return (new MailMessage)
            ->subject('Wat andere animatoren bewaren op Hartverwarmers')
            ->markdown('emails.onboarding-top-five', [
                'notifiable' => $notifiable,
                'recentFiches' => $recentFiches,
                'allTimeFiches' => $allTimeFiches,
            ]);
    }
}
