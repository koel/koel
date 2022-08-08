<?php

namespace Tests\Unit\Stubs;

use App\Services\ApiClients\ApiClient;

class ConcreteApiClient extends ApiClient
{
    public function getKey(): string
    {
        return 'bar';
    }

    public function getSecret(): string
    {
        return 'secret';
    }

    public function getEndpoint(): string
    {
        return 'https://foo.com';
    }
}
