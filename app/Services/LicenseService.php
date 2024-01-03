<?php

namespace App\Services;

class LicenseService
{
    public function isPlus(): bool
    {
        // @todo Implement checking for Plus license
        return true;
    }

    public function isCommunity(): bool
    {
        return !$this->isPlus();
    }
}
