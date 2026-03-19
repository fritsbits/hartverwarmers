<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

class ServerHealthAlertNotification extends Notification
{
    /**
     * @param  array<string, array{value: string, threshold: string}>  $violations
     */
    public function __construct(
        public array $violations = [],
        public bool $recovered = false,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['telegram'];
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        $env = app()->environment();

        if ($this->recovered) {
            return TelegramMessage::create()
                ->to(config('services.telegram-bot-api.chat_id'))
                ->line('✅ *Server health hersteld*')
                ->line('')
                ->line('Alle waarden zijn weer onder de drempelwaarden.')
                ->line('')
                ->line("*Environment:* `{$env}`");
        }

        $message = TelegramMessage::create()
            ->to(config('services.telegram-bot-api.chat_id'))
            ->line('⚠️ *Server health alert*')
            ->line('');

        foreach ($this->violations as $metric => $data) {
            $message->line("*{$metric}:* {$data['value']} ← drempel: {$data['threshold']}");
        }

        $message->line('');
        $message->line("*Environment:* `{$env}`");

        return $message;
    }
}
