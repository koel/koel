<?php

namespace App\Providers;

use App\Services\Util;
use Illuminate\Support\ServiceProvider;

class UtilServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        app()->singleton('Util', static function (): Util {
            return app(Util::class);
        });
    }
}
