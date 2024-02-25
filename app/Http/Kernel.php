<?php

namespace App\Http;

use App\Http\Middleware\AudioAuthenticate;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\ObjectStorageAuthenticate;
use App\Http\Middleware\ThrottleRequests;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustHosts;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Routing\Middleware\SubstituteBindings;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array<int,class-string>
     */
    protected $middleware = [
        CheckForMaintenanceMode::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ForceHttps::class,
        TrustHosts::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string,array<int,string>>
     */
    protected $middlewareGroups = [
        'web' => [
            'bindings',
        ],
        'api' => [
            'throttle:60,1',
            'bindings',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * @var array<string,class-string>
     */
    protected $routeMiddleware = [
        'auth' => Authenticate::class,
        'audio.auth' => AudioAuthenticate::class,
        'os.auth' => ObjectStorageAuthenticate::class,
        'bindings' => SubstituteBindings::class,
        'can' => Authorize::class,
        'throttle' => ThrottleRequests::class,
    ];
}
