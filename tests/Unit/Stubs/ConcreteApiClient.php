<?php

namespace Tests\Unit\Stubs;

use App\Services\ApiClient;

class ConcreteApiClient extends ApiClient
{
    public function getKey()
    {
        return 'bar';
    }

    public function getSecret()
    {
    }

    public function getEndpoint()
    {
        return 'http://foo.com';
    }
}
