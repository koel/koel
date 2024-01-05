<?php

namespace App\Services\ApiClients;

class LemonSqueezyApiClient extends ApiClient
{
    protected array $headers = [
        'Accept' => 'application/json',
    ];

    public function post($uri, array $data = [], bool $appendKey = true, array $headers = []): mixed
    {
        // LemonSquzzey requires the Content-Type header to be set to application/x-www-form-urlencoded
        // @see https://docs.lemonsqueezy.com/help/licensing/license-api#requests
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';

        return parent::post($uri, $data, $appendKey, $headers);
    }

    public function getKey(): ?string
    {
        return null;
    }

    public function getSecret(): ?string
    {
        return null;
    }

    public function getEndpoint(): ?string
    {
        return 'https://api.lemonsqueezy.com/v1/';
    }
}
