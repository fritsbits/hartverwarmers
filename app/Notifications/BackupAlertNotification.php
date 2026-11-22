<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

class BackupAlertNotification extends Notification
{
    public function __construct(public string $message, public string $channel = 'mail') {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return [$this->channel];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Back-upwaarschuwing — Hartverwarmers')
            ->line($this->message)
            ->line('Omgeving: '.app()->environment())
            ->line('Controleer de serverlogs en docs/backups.md. Deel geen sleutels, wachtwoorden of exports.');
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        return TelegramMessage::create()
            ->to(config('services.telegram-bot-api.chat_id'))
            ->line('⚠️ Back-up Hartverwarmers')
            ->line($this->message)
            ->line('Omgeving: '.app()->environment());
    }
}
