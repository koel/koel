<?php

namespace App\Providers;

use App\Services\Util;
use Illuminate\Support\ServiceProvider;

class UtilServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        app()->singleton('Util', static fn (): Util => app(Util::class));
    }
}
