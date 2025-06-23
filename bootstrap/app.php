<?php

use App\Http\Middleware\AudioAuthenticate;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\HandleDemoMode;
use App\Http\Middleware\ObjectStorageAuthenticate;
use App\Http\Middleware\RestrictPlusFeatures;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        using: static function (): void {
            RouteServiceProvider::loadVersionAwareRoutes('web');
            RouteServiceProvider::loadVersionAwareRoutes('api');
        },
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(static function (Middleware $middleware): void {
        $middleware->api(append: [
            RestrictPlusFeatures::class,
            HandleDemoMode::class,
            ForceHttps::class,
        ]);

        $middleware->web(append: [
            RestrictPlusFeatures::class,
            HandleDemoMode::class,
            ForceHttps::class,
        ]);

        $middleware->alias([
            'audio.auth' => AudioAuthenticate::class,
            'os.auth' => ObjectStorageAuthenticate::class,
        ]);
    })
    ->withExceptions(static function (Exceptions $exceptions): void {
        $exceptions->render(
            static function (AuthenticationException $e, Request $request): JsonResponse|RedirectResponse {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Unauthenticated.'], 401);
                }

                return redirect()->guest('/');
            }
        );
    })->create();
