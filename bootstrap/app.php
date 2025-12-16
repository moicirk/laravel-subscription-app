<?php

use App\Exceptions\CustomerExceptionHandle;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/health.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $customException = new CustomerExceptionHandle;

        $exceptions->report(function (Throwable $e) use ($customException) {
            $customException->report($e);
        });

        $exceptions->render(function (Throwable $e, $request) use ($customException) {
            return $customException->render($e, $request);
        });
    })->create();
