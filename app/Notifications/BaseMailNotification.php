<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Middleware\RateLimited;

abstract class BaseMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function middleware(): array
    {
        return [new RateLimited('resend')];
    }
}
