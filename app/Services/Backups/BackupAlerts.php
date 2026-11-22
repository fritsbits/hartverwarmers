<?php

namespace App\Services\Backups;

use App\Notifications\BackupAlertNotification;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class BackupAlerts
{
    public function recordFailure(Throwable $exception): void
    {
        $context = ['exception_type' => $exception::class];

        if ($exception instanceof AwsException) {
            $context['aws_error_code'] = $exception->getAwsErrorCode();
            $context['http_status'] = $exception->getStatusCode();
        } elseif ($exception::class === \RuntimeException::class) {
            $context['reason'] = $exception->getMessage();
        }

        Log::error('Backup operation failed.', $context);
    }

    public function send(string $message): void
    {
        Log::error('Backup: '.$message);

        $recipient = (string) config('backup.notifications.mail.to');

        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) && ! str_ends_with($recipient, '.invalid')) {
            try {
                Notification::route('mail', $recipient)
                    ->notify(new BackupAlertNotification($message));
            } catch (Throwable) {
                Log::error('Backup notification could not be delivered by mail. Check mail transport and recipient.');
            }
        } else {
            Log::error('Backup mail recipient is not configured.');
        }

        if (config('services.telegram-bot-api.token') && config('services.telegram-bot-api.chat_id')) {
            try {
                Notification::route('telegram', config('services.telegram-bot-api.chat_id'))
                    ->notify(new BackupAlertNotification($message, 'telegram'));
            } catch (Throwable) {
                Log::error('Backup notification could not be delivered by Telegram.');
            }
        }
    }
}
