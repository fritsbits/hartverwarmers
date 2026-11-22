<?php

namespace App\Providers;

use App\Services\Backups\BackupSafety;
use App\Services\Backups\SecureBackupCommand;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\Backup\Commands\BackupCommand;
use Spatie\Backup\Events\BackupZipWasCreated;

class BackupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BackupCommand::class, SecureBackupCommand::class);
    }

    public function boot(): void
    {
        Event::listen(BackupZipWasCreated::class, function (BackupZipWasCreated $event): void {
            app(BackupSafety::class)->verifyEncryptedArchive($event->pathToZip);
        });
    }
}
