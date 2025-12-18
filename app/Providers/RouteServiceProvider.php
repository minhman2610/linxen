<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to controller routes.
     */
    protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define the routes for the application.
     */
    public function boot(): void
    {
        parent::boot();

        $this->routes(function () {

            // ✅ API routes
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            // ✅ Web routes
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
