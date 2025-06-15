<?php

namespace App\Http\Integrations\Lastfm;

use App\Http\Integrations\Lastfm\Auth\LastfmAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class LastfmConnector extends Connector
{
    use AcceptsJson;

    public function resolveBaseUrl(): string
    {
        return config('koel.services.lastfm.endpoint');
    }

    protected function defaultAuth(): LastfmAuthenticator
    {
        return new LastfmAuthenticator(config('koel.services.lastfm.key'), config('koel.services.lastfm.secret'));
    }
}
