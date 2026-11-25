<?php

namespace App\Notifications;

use App\Services\Backups\BackupAlerts;
use Spatie\Backup\Notifications\Notifiable;

class BackupNotifiable extends Notifiable
{
    /**
     * Replace package messages containing raw exceptions with fixed messages.
     */
    public function notify($instance): void
    {
        if (! config('backup.notifications.notifications.'.$instance::class, [])) {
            return;
        }

        $message = match (class_basename($instance)) {
            'BackupHasFailedNotification' => 'Het app-/databasearchief is mislukt.',
            'CleanupHasFailedNotification' => 'Het bewaarbeleid voor app-/databasearchieven kon niet worden uitgevoerd.',
            'UnhealthyBackupWasFoundNotification' => 'Het app-/databasearchief ontbreekt, is te oud, te groot of onbereikbaar.',
            default => 'Controleer de status van de app-/databaseback-up.',
        };

        app(BackupAlerts::class)->send($message);
    }
}
