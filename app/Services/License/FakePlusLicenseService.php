<?php

namespace App\Services\License;

use App\Exceptions\MethodNotImplementedException;
use App\Models\License;
use App\Values\LicenseStatus;

class FakePlusLicenseService implements LicenseServiceInterface
{
    public function activate(string $key): License
    {
        throw MethodNotImplementedException::method(__METHOD__);
    }

    public function deactivate(License $license): void
    {
        throw MethodNotImplementedException::method(__METHOD__);
    }

    public function getStatus(): LicenseStatus
    {
        throw MethodNotImplementedException::method(__METHOD__);
    }

    public function isPlus(): bool
    {
        return true;
    }

    public function isCommunity(): bool
    {
        return false;
    }
}
