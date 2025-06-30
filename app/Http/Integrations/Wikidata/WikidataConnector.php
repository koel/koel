<?php

namespace App\Http\Integrations\Wikidata;

use Saloon\Http\Connector;

class WikidataConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return 'https://www.wikidata.org/wiki/';
    }

    /** @inheritdoc */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }
}
