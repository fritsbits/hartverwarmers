<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(\App\Http\Middleware\RedirectTrailingSlash::class);
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'curator' => \App\Http\Middleware\EnsureUserIsCurator::class,
        ]);
        $middleware->appendToGroup('web', \App\Http\Middleware\HandleImpersonation::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureQueueWorkerRunning::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\TrackLastVisit::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
