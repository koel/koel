<?php

namespace App\Http\Integrations\Concerns;

use Illuminate\Http\Response as HttpResponse;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

trait OnlyThrowsOnServerErrorsAndRateLimits
{
    use AlwaysThrowOnErrors;

    public function hasRequestFailed(Response $response): ?bool
    {
        return $response->serverError() || $response->status() === HttpResponse::HTTP_TOO_MANY_REQUESTS;
    }
}
