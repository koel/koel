<?php

namespace App\Values;

use Webmozart\Assert\Assert;

final class SyncResult
{
    public const TYPE_SUCCESS = 1;
    public const TYPE_ERROR = 2;
    public const TYPE_SKIPPED = 3;

    private function __construct(public string $path, public int $type, public ?string $error)
    {
        Assert::oneOf($type, [
            SyncResult::TYPE_SUCCESS,
            SyncResult::TYPE_ERROR,
            SyncResult::TYPE_SKIPPED,
        ]);
    }

    public static function success(string $path): self
    {
        return new self($path, self::TYPE_SUCCESS, null);
    }

    public static function skipped(string $path): self
    {
        return new self($path, self::TYPE_SKIPPED, null);
    }

    public static function error(string $path, ?string $error): self
    {
        return new self($path, self::TYPE_ERROR, $error);
    }

    public function isSuccess(): bool
    {
        return $this->type === self::TYPE_SUCCESS;
    }

    public function isSkipped(): bool
    {
        return $this->type === self::TYPE_SKIPPED;
    }

    public function isError(): bool
    {
        return $this->type === self::TYPE_ERROR;
    }

    public function isValid(): bool
    {
        return $this->isSuccess() || $this->isSkipped();
    }
}
