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
        return config('koel.lastfm.endpoint');
    }

    protected function defaultAuth(): LastfmAuthenticator
    {
        return new LastfmAuthenticator(config('koel.lastfm.key'), config('koel.lastfm.secret'));
    }
}
