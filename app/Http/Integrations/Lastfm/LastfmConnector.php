<?php

namespace App\Http\Integrations\Lastfm;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class LastfmConnector extends Connector
{
    use AcceptsJson;

    public function resolveBaseUrl(): string
    {
        return config('koel.lastfm.endpoint');
    }
}
