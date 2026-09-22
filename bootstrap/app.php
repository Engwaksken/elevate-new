<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',

        then: function () {
            $webRoutes = [
                'web.phase2.php',
                'web.phase3.php',
                'web.phase4.php',
                'web.phase5.php',
                'web.phase6.php',
                'web.phase7.php',
                'web.phase8.php',
                'web.phase9.php',
                'web.phase10.php',
                'web.phase11.php',
            ];

            foreach ($webRoutes as $routeFile) {
                $path = base_path('routes/'.$routeFile);

                if (file_exists($path)) {
                    Route::middleware('web')->group($path);
                }
            }

            $apiPath = base_path('routes/api.phase11.php');

            if (file_exists($apiPath)) {
                Route::middleware('api')
                    ->prefix('api')
                    ->group($apiPath);
            }
        },
    )

    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'staff' => \App\Http\Middleware\EnsureStaffUser::class,
            'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
        ]);

        $middleware->append(
            \App\Http\Middleware\SecurityHeaders::class
        );
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })

    ->create();