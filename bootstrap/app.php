<?php

use App\Http\Middleware\AudioAuthenticate;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\HandleDemoMode;
use App\Http\Middleware\ObjectStorageAuthenticate;
use App\Http\Middleware\RestrictPlusFeatures;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        using: static function (): void {
            RouteServiceProvider::loadVersionAwareRoutes('web');
            RouteServiceProvider::loadVersionAwareRoutes('api');
            Route::middleware('api')->group(base_path('routes/subsonic.php'));
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

        // Koel is an SPA without a `login` route, so the Authenticate middleware would otherwise
        // throw RouteNotFoundException when it tries to resolve route('login') on guest requests.
        $middleware->redirectGuestsTo('/');
    })
    ->withExceptions(static function (Exceptions $exceptions): void {
        $exceptions->render(static function (
            AuthenticationException $e,
            Request $request,
        ): JsonResponse|RedirectResponse {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest('/');
        });

        $exceptions->render(static function (NotFoundHttpException $e, Request $request): ?SymfonyResponse {
            return $request->is('rest/*')
                ? SubsonicResponse::error(70, 'The requested data was not found.')->toResponse($request)
                : null;
        });

        $exceptions->render(static function (ValidationException $e, Request $request): ?SymfonyResponse {
            return $request->is('rest/*')
                ? SubsonicResponse::error(10, 'Required parameter is missing.')->toResponse($request)
                : null;
        });
    })
    ->create();
