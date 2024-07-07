<?php

namespace Tests\Fakes;

use App\Exceptions\MethodNotImplementedException;
use App\Models\License;
use App\Services\License\Contracts\LicenseServiceInterface;
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

    public function getStatus(bool $checkCache = true): LicenseStatus
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
