<?php

namespace App\Http;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\GetUserFromToken;
use App\Http\Middleware\ObjectStorageAuthenticate;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\UseDifferentConfigIfE2E;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        CheckForMaintenanceMode::class,
        UseDifferentConfigIfE2E::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            // Koel doesn't use any default Laravel middleware for web.
        ],
        'api' => [
            'throttle:60,1',
            'bindings',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => Authenticate::class,
        'jwt.auth' => GetUserFromToken::class,
        'os.auth' => ObjectStorageAuthenticate::class,
        'bindings' => SubstituteBindings::class,
        'can' => Authorize::class,
        'throttle' => ThrottleRequests::class,
    ];
}
