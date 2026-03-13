<?php

use App\Http\Middleware\EnsureCustomerActive;
use App\Http\Middleware\EnsureDeviceRegistered;
use App\Http\Middleware\VerifyTransactionPin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'mobile.active' => EnsureCustomerActive::class,
            'mobile.pin' => VerifyTransactionPin::class,
            'mobile.device' => EnsureDeviceRegistered::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
