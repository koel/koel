<?php

namespace App\Http\Integrations\Lastfm\Requests;

use App\Http\Integrations\Lastfm\Contracts\RequiresSignature;
use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetSessionKeyRequest extends Request implements RequiresSignature
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $token)
    {
    }

    public function resolveEndpoint(): string
    {
        return '/';
    }

    /** @return array<mixed> */
    protected function defaultQuery(): array
    {
        return [
            'method' => 'auth.getSession',
            'token' => $this->token,
            'format' => 'json',
        ];
    }
}
