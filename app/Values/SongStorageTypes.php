<?php

namespace App\Values;

use App\Facades\License;
use Webmozart\Assert\Assert;

final class SongStorageTypes
{
    public const S3 = 's3';
    public const S3_LAMBDA = 's3-lambda';
    public const DROPBOX = 'dropbox';
    public const LOCAL = null;

    public const ALL_TYPES = [
        self::S3,
        self::S3_LAMBDA,
        self::DROPBOX,
        self::LOCAL,
    ];

    public static function assertValidType(?string $type): void
    {
        Assert::oneOf($type, self::ALL_TYPES, "Invalid storage type: $type");
    }

    public static function supported(?string $type): bool
    {
        if (!$type || $type === self::S3_LAMBDA) {
            return true;
        }

        return License::isPlus();
    }
}
