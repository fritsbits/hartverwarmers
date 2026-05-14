<?php

namespace App\Providers;

use App\Services\Okr\MetricRegistry;
use Illuminate\Support\ServiceProvider;

class OkrServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            MetricRegistry::class,
            fn () => new MetricRegistry(config('okr-metrics', [])),
        );
    }
}
