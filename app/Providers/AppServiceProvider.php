<?php

namespace App\Providers;

use App\Events\CommentPosted;
use App\Listeners\SendFicheCommentNotification;
use App\Listeners\SendWelcomeNotification;
use App\Notifications\QueueJobFailedNotification;
use App\View\Composers\AboutComposer;
use App\View\Composers\FooterComposer;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.layout', FooterComposer::class);
        View::composer('about', AboutComposer::class);

        Feature::discover();

        EnsureFeaturesAreActive::whenInactive(fn () => abort(404));

        Event::listen(Verified::class, SendWelcomeNotification::class);

        Event::listen(
            CommentPosted::class,
            SendFicheCommentNotification::class,
        );

        $this->listenForFailedJobs();

        $this->customizeMailNotifications();
    }

    private function listenForFailedJobs(): void
    {
        if (! config('services.telegram-bot-api.token') || ! config('services.telegram-bot-api.chat_id')) {
            return;
        }

        Queue::failing(function (JobFailed $event) {
            Notification::route('telegram', config('services.telegram-bot-api.chat_id'))
                ->notify(new QueueJobFailedNotification(
                    jobName: $event->job->resolveName(),
                    exceptionMessage: $event->exception->getMessage(),
                    queue: $event->job->getQueue(),
                ));
        });
    }

    private function customizeMailNotifications(): void
    {
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
                ->subject('Bevestig je e-mailadres — Hartverwarmers')
                ->greeting("Hoi {$notifiable->first_name}!")
                ->line('Bevestig je e-mailadres om je account te activeren.')
                ->action('Bevestig je e-mailadres', $url)
                ->line('Heb je geen account aangemaakt? Dan hoef je niets te doen.')
                ->salutation("Warme groet,\nHet Hartverwarmers-team");
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Wachtwoord resetten — Hartverwarmers')
                ->greeting("Hoi {$notifiable->first_name}!")
                ->line('Je ontvangt deze e-mail omdat we een verzoek kregen om je wachtwoord te resetten.')
                ->action('Wachtwoord resetten', $url)
                ->line('Deze link is '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minuten geldig.')
                ->line('Heb je dit niet aangevraagd? Dan hoef je niets te doen.')
                ->salutation("Warme groet,\nHet Hartverwarmers-team");
        });
    }
}
