<?php

namespace App\Http\Integrations\Listenbrainz;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class ListenbrainzConnector extends Connector
{
    use AcceptsJson;

    public function resolveBaseUrl(): string
    {
        return config('koel.services.listenbrainz.endpoint');
    }
}
