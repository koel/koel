<?php

use App\Exceptions\SubsonicAwareErrorRenderer;
use App\Http\Middleware\AuthenticateAudioRequests;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
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
            'audio.auth' => AuthenticateAudioRequests::class,
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

        $exceptions->render(
            static fn (Throwable $e, Request $request): ?SymfonyResponse => SubsonicAwareErrorRenderer::render(
                $e,
                $request,
            ),
        );

        // Surface Subsonic clients hitting unmapped routes so we can implement missing endpoints.
        // NotFoundHttpException is normally on Laravel's internalDontReport list.
        $exceptions->reportable(static function (NotFoundHttpException $e): bool {
            if (request()->is('rest/*')) {
                Log::error('Missing Subsonic route: ' . $e->getMessage(), ['exception' => $e]);
            }

            return false;
        });
    })
    ->create();
