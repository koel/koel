<?php

namespace App\Providers;

use App\Services\YouTubeService;
use Illuminate\Support\ServiceProvider;

class YouTubeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        app()->singleton('YouTube', static function (): YouTubeService {
            return app(YouTubeService::class);
        });
    }
}
