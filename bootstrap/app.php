<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\EnsureProviderAccountActive;
use App\Http\Middleware\EnsureProviderDocumentVerified;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'prevent-back-history' => PreventBackHistory::class,
            'provider.account.active' => EnsureProviderAccountActive::class,
        'provider.document.verified' => EnsureProviderDocumentVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
