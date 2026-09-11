<?php

namespace App\Http\Integrations\ListenBrainz\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use SensitiveParameter;

final class ValidateTokenRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        #[SensitiveParameter]
        private readonly string $token,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/1/validate-token';
    }

    /** @inheritdoc */
    protected function defaultHeaders(): array
    {
        return ['Authorization' => "Token {$this->token}"];
    }
}
