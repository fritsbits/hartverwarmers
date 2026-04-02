<?php

namespace App\Notifications;

use App\Models\Initiative;
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
        $sparseInitiatives = Initiative::published()
            ->withCount(['fiches as published_fiches_count' => fn ($q) => $q->where('published', true)])
            ->having('published_fiches_count', '>=', 1)
            ->having('published_fiches_count', '<=', 5)
            ->inRandomOrder()
            ->limit(3)
            ->get();

        return (new MailMessage)
            ->subject("{$this->bookmarkCount} mensen bewaarden jouw fiches — bedankt!")
            ->markdown('emails.onboarding-milestone-10-bookmarks', [
                'notifiable' => $notifiable,
                'bookmarkCount' => $this->bookmarkCount,
                'sparseInitiatives' => $sparseInitiatives,
            ]);
    }
}
