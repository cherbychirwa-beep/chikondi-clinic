<?php

use App\Exceptions\AuthorizationException;
use App\Http\Middleware\CheckInactivity;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\RequirePermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureAccountActive::class,
            'inactivity' => CheckInactivity::class,
            'password.fresh' => EnsurePasswordChanged::class,
            'permission' => RequirePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Domain authorisation failures are shown as a friendly 403 page.
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            return response()->view('errors.403', ['exception' => $e], 403);
        });
    })->create();
