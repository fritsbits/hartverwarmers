<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

class QueueJobFailedNotification extends Notification
{
    public function __construct(
        public string $jobName,
        public string $exceptionMessage,
        public string $queue,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['telegram'];
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        $env = app()->environment();

        return TelegramMessage::create()
            ->to(config('services.telegram-bot-api.chat_id'))
            ->line('🚨 *Queue job failed*')
            ->line('')
            ->line("*Job:* `{$this->jobName}`")
            ->line("*Queue:* `{$this->queue}`")
            ->line("*Environment:* `{$env}`")
            ->line('')
            ->line('*Error:*')
            ->line('`'.str($this->exceptionMessage)->limit(500).'`');
    }
}
