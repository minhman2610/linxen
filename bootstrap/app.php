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
    /* AI_PATCH_LINXEN_COMMERCE_V2_COMMAND_DISCOVERY_V1_START */
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    /* AI_PATCH_LINXEN_COMMERCE_V2_COMMAND_DISCOVERY_V1_END */
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
