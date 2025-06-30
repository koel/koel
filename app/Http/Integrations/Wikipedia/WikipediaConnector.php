<?php

namespace App\Http\Integrations\Wikipedia;

use Saloon\Http\Connector;

class WikipediaConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return 'https://en.wikipedia.org/api/rest_v1/';
    }

    /** @inheritdoc */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }
}
