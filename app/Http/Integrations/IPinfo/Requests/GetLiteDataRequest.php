<?php

namespace App\Http\Integrations\IPinfo\Requests;

use App\Values\IpInfoLiteData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetLiteDataRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(public readonly string $ip)
    {
    }

    public function resolveEndpoint(): string
    {
        return "lite/{$this->ip}";
    }

    public function createDtoFromResponse(Response $response): IpInfoLiteData
    {
        return IpInfoLiteData::fromSaloonResponse($response);
    }
}
