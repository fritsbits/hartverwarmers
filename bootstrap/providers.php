<?php

use App\Providers\AppServiceProvider;
use App\Providers\BackupServiceProvider;
use App\Providers\OkrServiceProvider;
use App\Providers\TelegramServiceProvider;

return [
    AppServiceProvider::class,
    BackupServiceProvider::class,
    OkrServiceProvider::class,
    TelegramServiceProvider::class,
];
