<?php

namespace App\Http\Integrations\YouTube;

use Saloon\Http\Auth\QueryAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class YouTubeConnector extends Connector
{
    use AcceptsJson;

    public function resolveBaseUrl(): string
    {
        return config('koel.youtube.endpoint');
    }

    protected function defaultAuth(): QueryAuthenticator
    {
        return new QueryAuthenticator('key', config('koel.youtube.key'));
    }
}
