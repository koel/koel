<?php

namespace App\Http\Integrations\Wikipedia;

use App\Http\Integrations\Concerns\ThrowsWhenUnavailable;
use Saloon\Http\Connector;

class WikipediaConnector extends Connector
{
    use ThrowsWhenUnavailable;

    public function resolveBaseUrl(): string
    {
        return 'https://en.wikipedia.org/api/rest_v1/';
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
