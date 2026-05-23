<?php

namespace App\Providers;

use App\Socialite\OpenIDConnect\Provider as OpenIDConnectProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;

class SocialiteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /** @var \Laravel\Socialite\SocialiteManager $socialite */
        $socialite = $this->app->make(SocialiteFactory::class);

        $socialite->extend('oidc', static function ($app) use ($socialite) {
            return $socialite->buildProvider(OpenIDConnectProvider::class, config('services.oidc'));
        });
    }
}
