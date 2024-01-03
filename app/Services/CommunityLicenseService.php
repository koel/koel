<?php

namespace App\Services;

class CommunityLicenseService extends LicenseService
{
    public function isPlus(): bool
    {
        return false;
    }
}
