<?php

namespace App\Providers;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use NotificationChannels\Telegram\Telegram;
use NotificationChannels\Telegram\TelegramChannel;

/**
 * Replacement for laravel-notification-channels/telegram's TelegramServiceProvider,
 * patched to remove static closures that break under Laravel 13's Manager::extend binding.
 */
class TelegramServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Telegram::class, fn () => new Telegram(
            config('services.telegram-bot-api.token'),
            app(HttpClient::class),
            config('services.telegram-bot-api.base_uri')
        ));

        Notification::resolved(function (ChannelManager $service) {
            $service->extend('telegram', fn ($app) => $app->make(TelegramChannel::class));
        });
    }
}
