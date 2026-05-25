<?php

namespace App\Providers;

use App\Socialite\OpenIDConnect\Provider as OpenIDConnectProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\SocialiteManager;

class SocialiteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /** @var SocialiteManager $socialite */
        $socialite = $this->app->make(SocialiteFactory::class);

        $socialite->extend('oidc', function (Application $app): OpenIDConnectProvider { // @mago-ignore lint:prefer-static-closure
            $config = config('services.oidc');

            return new OpenIDConnectProvider(
                $app->make(Request::class),
                Arr::get($config, 'client_id'),
                Arr::get($config, 'client_secret'),
                URL::to(Arr::get($config, 'redirect')),
                Arr::get($config, 'issuer'),
            );
        });
    }
}
