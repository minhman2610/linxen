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

        // 🔥 FIX CSRF CHO API STOREFRONT
        $middleware->validateCsrfTokens(except: [
            'api/storefront/*',
        ]);

        // ✅ REGISTER ROUTE MIDDLEWARE (LARAVEL 11)
    $middleware->alias([
        'storefront.auth' => \App\Http\Middleware\StorefrontAuth::class,
    ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
