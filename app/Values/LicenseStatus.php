<?php

namespace App\Values;

use App\Enums\LicenseStatus as Status;
use App\Models\License;

final class LicenseStatus
{
    private function __construct(public Status $status, public ?License $license)
    {
    }

    public function isValid(): bool
    {
        return $this->status === Status::VALID;
    }

    public function hasNoLicense(): bool
    {
        return $this->status === Status::NO_LICENSE;
    }

    public static function noLicense(): self
    {
        return new self(Status::NO_LICENSE, null);
    }

    public static function valid(License $license): self
    {
        return new self(Status::VALID, $license);
    }

    public static function invalid(License $license): self
    {
        return new self(Status::INVALID, $license);
    }

    public static function unknown(License $license): self
    {
        return new self(Status::UNKNOWN, $license);
    }
}
