<?php

use App\Http\Middleware\EnsureQueueWorkerRunning;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsCurator;
use App\Http\Middleware\HandleImpersonation;
use App\Http\Middleware\RedirectTrailingSlash;
use App\Http\Middleware\TrackLastVisit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(RedirectTrailingSlash::class);
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'curator' => EnsureUserIsCurator::class,
        ]);
        $middleware->appendToGroup('web', HandleImpersonation::class);
        $middleware->appendToGroup('web', EnsureQueueWorkerRunning::class);
        $middleware->appendToGroup('web', TrackLastVisit::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);
    })->create();
