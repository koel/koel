<?php

namespace App\Http\Integrations\YouTube;

use Saloon\Http\Auth\QueryAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;
use SensitiveParameter;

class YouTubeConnector extends Connector
{
    use AcceptsJson;

    public function __construct(
        #[SensitiveParameter]
        private readonly string $apiKey,
    ) {}

    public function resolveBaseUrl(): string
    {
        return config('koel.services.youtube.endpoint');
    }

    protected function defaultAuth(): QueryAuthenticator
    {
        return new QueryAuthenticator('key', $this->apiKey);
    }
}
