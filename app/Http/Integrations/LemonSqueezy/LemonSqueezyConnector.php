<?php

namespace App\Http\Integrations\LemonSqueezy;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class LemonSqueezyConnector extends Connector
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;

    public function resolveBaseUrl(): string
    {
        return 'https://api.lemonsqueezy.com/v1/';
    }
}
