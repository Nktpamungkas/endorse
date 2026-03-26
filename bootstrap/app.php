<?php

use App\Http\Middleware\EnsureSingleUserAuthenticated;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetSessionLifetime;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'single.auth' => EnsureSingleUserAuthenticated::class,
            'session.dynamic' => SetSessionLifetime::class,
        ]);

        $middleware->prependToGroup('web', SetSessionLifetime::class);
        $middleware->appendToGroup('web', HandleInertiaRequests::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sesi keamanan habis. Silakan muat ulang halaman lalu coba lagi.',
                ], 419);
            }

            $message = 'Sesi keamanan habis. Silakan buka login lagi lalu coba ulang.';

            if ($request->routeIs('login.attempt') || $request->is('login')) {
                return redirect()
                    ->route('login.form')
                    ->withErrors(['username' => $message])
                    ->withInput($request->only('username'));
            }

            return redirect()
                ->route('login.form')
                ->with('error', $message);
        });
    })
    ->create();
