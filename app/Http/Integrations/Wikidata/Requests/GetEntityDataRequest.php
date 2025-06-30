<?php

namespace App\Http\Integrations\Wikidata\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetEntityDataRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $entityId)
    {
    }

    public function resolveEndpoint(): string
    {
        return "Special:EntityData/{$this->entityId}";
    }
}
