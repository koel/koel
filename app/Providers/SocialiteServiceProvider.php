<?php

namespace App\Providers;

use App\Socialite\OpenIDConnect\Provider as OpenIDConnectProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\SocialiteManager;

class SocialiteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /** @var SocialiteManager $socialite */
        $socialite = $this->app->make(SocialiteFactory::class);

        $socialite->extend('oidc', static function (Application $app) use ($socialite) {
            return $socialite->buildProvider(OpenIDConnectProvider::class, config('services.oidc'));
        });
    }
}
