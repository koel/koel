<?php

namespace App\Values;

use App\Models\License;
use Webmozart\Assert\Assert;

final class LicenseStatus
{
    public const STATUS_VALID = 1;
    public const STATUS_INVALID = 2;
    public const STATUS_NO_LICENSE = 3;
    public const STATUS_UNKNOWN = 4;

    private function __construct(public int $status, public ?License $license)
    {
        Assert::oneOf($this->status, [
            self::STATUS_NO_LICENSE,
            self::STATUS_INVALID,
            self::STATUS_VALID,
            self::STATUS_UNKNOWN,
        ]);
    }

    public function isValid(): bool
    {
        return $this->status === self::STATUS_VALID;
    }

    public function hasNoLicense(): bool
    {
        return $this->status === self::STATUS_NO_LICENSE;
    }

    public static function noLicense(): self
    {
        return new self(self::STATUS_NO_LICENSE, null);
    }

    public static function valid(License $license): self
    {
        return new self(self::STATUS_VALID, $license);
    }

    public static function invalid(License $license): self
    {
        return new self(self::STATUS_INVALID, $license);
    }

    public static function unknown(License $license): self
    {
        return new self(self::STATUS_UNKNOWN, $license);
    }
}
