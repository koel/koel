<?php

namespace App\Http\Integrations\CoverArtArchive\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetReleaseCoverRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $mbid,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/release/{$this->mbid}";
    }
}
