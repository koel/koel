<?php

namespace App\Http\Integrations\Wikipedia\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetPageSummaryRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $pageTitle)
    {
    }

    public function resolveEndpoint(): string
    {
        return "page/summary/{$this->pageTitle}";
    }
}
