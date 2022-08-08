<?php

namespace App\Services\ApiClients;

class ITunesClient extends ApiClient
{
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
        return config('koel.itunes.endpoint');
    }
}
