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
Schedule::command('onboarding:send-emails')->dailyAt('08:00');
Schedule::command('notifications:send-digests --frequency=daily')->dailyAt('08:00');
Schedule::command('notifications:send-digests --frequency=weekly')->weeklyOn(1, '08:00');
Schedule::command('newsletter:send-monthly-cohort')
    ->dailyAt('08:00')
    ->timezone('Europe/Brussels');
