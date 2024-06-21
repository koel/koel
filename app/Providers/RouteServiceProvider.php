<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\File;
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

        $apiVersion = self::getApiVersion();
        $routeFile = $apiVersion ? base_path(sprintf('routes/%s.%s.php', $type, $apiVersion)) : null;

        if ($routeFile && File::exists($routeFile)) {
            Route::group([], $routeFile);
        }
    }

    private static function getApiVersion(): ?string
    {
        // In the test environment, the route service provider is loaded _before_ the request is made,
        // so we can't rely on the header.
        // Instead, we manually set the API version as an env variable in applicable test cases.
        $version = app()->runningUnitTests() ? env('X_API_VERSION') : request()->header('X-Api-Version');

        if ($version) {
            Assert::oneOf($version, ['v6', 'v7']);
        }

        return $version;
    }
}
