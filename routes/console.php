<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('fiches:assign-icons')->everyFiveMinutes();
Schedule::command('fiches:assess-quality --limit=20')->hourly()->withoutOverlapping();
Schedule::command('file:cleanup-orphans')->daily()->at('03:00');
Schedule::command('queue:heartbeat')->everyFiveMinutes();
Schedule::command('queue:prune-failed --hours=168')->daily();
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
Schedule::command('newsletter:send-reactivation')
    ->dailyAt('10:00')
    ->timezone('Europe/Brussels');
Schedule::command('newsletter:send-monthly-cohort')
    ->dailyAt('10:30')
    ->timezone('Europe/Brussels');
Schedule::command('onboarding:send-emails')
    ->dailyAt('11:30')
    ->timezone('Europe/Brussels');
Schedule::command('okr:warm-metrics')->hourly()->withoutOverlapping();
Schedule::command('themes:health-check')
    ->weeklyOn(1, '09:00')
    ->timezone('Europe/Brussels');

// Diamantje van de maand: suggestion mail to the admin a few days ahead,
// automatic award on the 1st — before the 10:30 digest batch, so no digest
// ever goes out with a stale diamond.
Schedule::command('diamonds:send-rotation-suggestion')
    ->monthlyOn(27, '09:00')
    ->timezone('Europe/Brussels');
Schedule::command('diamonds:rotate')
    ->monthlyOn(1, '06:00')
    ->timezone('Europe/Brussels');
