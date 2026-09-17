<?php

namespace App\Http\Integrations\Wikidata;

use App\Http\Integrations\Concerns\ThrowsWhenUnavailable;
use Saloon\Http\Connector;

class WikidataConnector extends Connector
{
    use ThrowsWhenUnavailable;

    public function resolveBaseUrl(): string
    {
        return 'https://www.wikidata.org/wiki/';
    }

    /** @inheritdoc */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'User-Agent' => koel_user_agent(),
        ];
    }
}
