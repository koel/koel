<?php

namespace App\Http;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\GetUserFromToken;
use App\Http\Middleware\ObjectStorageAuthenticate;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        CheckForMaintenanceMode::class,
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
    ];
}
