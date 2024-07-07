<?php

namespace App\Http\Integrations\iTunes;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class ITunesConnector extends Connector
{
    use AcceptsJson;

    public function resolveBaseUrl(): string
    {
        return config('koel.itunes.endpoint');
    }
}
