<?php

namespace App\Http\Integrations\ListenBrainz;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class ListenBrainzConnector extends Connector
{
    use AcceptsJson;

    public function resolveBaseUrl(): string
    {
        return config('koel.services.listenbrainz.endpoint');
    }
}
