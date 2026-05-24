<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\EnsureProviderAccountActive;
use App\Http\Middleware\EnsureProviderDocumentVerified;
use App\Http\Middleware\EnsureProviderMenuPermission;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'prevent-back-history' => PreventBackHistory::class,
            'provider.account.active' => EnsureProviderAccountActive::class,
            'provider.document.verified' => EnsureProviderDocumentVerified::class,
            'provider.menu' => EnsureProviderMenuPermission::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            return $request->is('provider/*') || $request->is('provider-branch/*')
                ? route('provider.login')
                : route('admin.login');
        });

        $middleware->validateCsrfTokens(except: [
            'provider/signin',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
