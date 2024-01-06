<?php

namespace App\Services;

use App\Services\ApiClients\LemonSqueezyApiClient;

class CommunityLicenseService extends LicenseService
{
    public function __construct(LemonSqueezyApiClient $client)
    {
        parent::__construct($client, config('app.key'));
    }

    public function isPlus(): bool
    {
        return false;
    }
}
