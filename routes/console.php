<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('themes:rollover')->daily()->at('00:05');
Schedule::command('fiches:assign-icons')->everyFiveMinutes();
Schedule::command('file:cleanup-orphans')->daily()->at('03:00');
Schedule::command('queue:heartbeat')->everyFiveMinutes();
Schedule::command('server:health-check')->everyFiveMinutes();
// Engagement emails are staggered across the morning so they don't pile
// into the 08:00 inbox flood. Ordering also serves the 24h cap: rarest
// email types (anniversary, then newsletter) fire BEFORE the wider-window
// onboarding sequence so they win the cap on rare collision days.
Schedule::command('notifications:send-digests --frequency=daily')
    ->dailyAt('07:00')
    ->timezone('Europe/Brussels');
Schedule::command('notifications:send-digests --frequency=weekly')
    ->weeklyOn(2, '09:00') // Tuesday — Mondays are the busiest mailbox of the week
    ->timezone('Europe/Brussels');
Schedule::command('contributors:send-anniversary-emails')
    ->dailyAt('09:30')
    ->timezone('Europe/Brussels');
Schedule::command('newsletter:send-monthly-cohort')
    ->dailyAt('10:30')
    ->timezone('Europe/Brussels');
Schedule::command('onboarding:send-emails')
    ->dailyAt('11:30')
    ->timezone('Europe/Brussels');
Schedule::command('okr:warm-metrics')->hourly()->withoutOverlapping();
