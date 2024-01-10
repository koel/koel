<?php

namespace App\Services\License;

use App\Models\License;
use App\Values\LicenseStatus;

interface LicenseServiceInterface
{
    public function isPlus(): bool;

    public function isCommunity(): bool;

    public function activate(string $key): License;

    public function deactivate(License $license): void;

    public function getStatus(bool $checkCache = true): LicenseStatus;
}
