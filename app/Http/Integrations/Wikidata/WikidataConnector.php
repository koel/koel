<?php

namespace App\Http\Integrations\Wikidata;

use App\Http\Integrations\Concerns\OnlyThrowsOnServerErrorsAndRateLimits;
use Saloon\Http\Connector;

class WikidataConnector extends Connector
{
    use OnlyThrowsOnServerErrorsAndRateLimits;

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
