<?php

namespace App\Http\Integrations\Lastfm\Requests;

use Saloon\Enums\Method;

final class GetSessionKeyRequest extends SignedRequest
{
    protected Method $method = Method::GET;

    public function __construct(private string $token)
    {
        parent::__construct();
    }

    public function resolveEndpoint(): string
    {
        return '/';
    }

    /** @return array<mixed> */
    protected function defaultQuery(): array
    {
        return [
            'api_key' => config('koel.lastfm.key'),
            'method' => 'auth.getSession',
            'token' => $this->token,
            'format' => 'json',
        ];
    }
}
