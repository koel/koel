<?php

namespace App\Providers;

use App\Services\MediaCacheService;
use Illuminate\Support\ServiceProvider;

class MediaCacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        app()->singleton('MediaCache', static function (): MediaCacheService {
            return app(MediaCacheService::class);
        });
    }
}
