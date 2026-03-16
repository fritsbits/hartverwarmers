<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('themes:rollover')->daily()->at('00:05');
Schedule::command('file:generate-previews --all')->everyFiveMinutes()->withoutOverlapping()->environments(['local']);
Schedule::command('file:cleanup-orphans')->daily()->at('03:00');
