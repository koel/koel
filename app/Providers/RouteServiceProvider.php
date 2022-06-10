<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Webmozart\Assert\Assert;

class RouteServiceProvider extends ServiceProvider
{
    protected $namespace = 'App\Http\Controllers';

    public function map(): void
    {
        self::loadVersionAwareRoutes('web');
        self::loadVersionAwareRoutes('api');
    }

    private static function loadVersionAwareRoutes(string $type): void
    {
        Assert::oneOf($type, ['web', 'api']);

        Route::group([], base_path(sprintf('routes/%s.base.php', $type)));

        $apiVersion = request()->header('X-Api-Version');
        $routeFile = $apiVersion ? base_path(sprintf('routes/%s.%s.php', $type, $apiVersion)) : null;

        if ($routeFile && file_exists($routeFile)) {
            Route::group([], $routeFile);
        }
    }
}
