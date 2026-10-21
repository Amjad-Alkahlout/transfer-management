<?php

use App\Http\Middleware\EnsureSingleDeviceSession;
use App\Http\Middleware\EnsureApiUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\SetLocale;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->alias([
            'single.device' => EnsureSingleDeviceSession::class,
            'api.active' => EnsureApiUserIsActive::class,
        ]);

        $middleware->validateCsrfTokens(
            except: [
                'telegram/webhook',
            ],
        );

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
