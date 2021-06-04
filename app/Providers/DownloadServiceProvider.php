<?php

namespace App\Providers;

use App\Services\DownloadService;
use Illuminate\Support\ServiceProvider;

class DownloadServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        app()->singleton('Download', static fn (): DownloadService => app(DownloadService::class));
    }
}
