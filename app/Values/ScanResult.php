<?php

namespace App\Values;

use Exception;
use Webmozart\Assert\Assert;

final class ScanResult
{
    public const TYPE_SUCCESS = 1;
    public const TYPE_ERROR = 2;
    public const TYPE_SKIPPED = 3;

    private function __construct(public string $path, public int $type, public ?string $error = null)
    {
        Assert::oneOf($type, [
            ScanResult::TYPE_SUCCESS,
            ScanResult::TYPE_ERROR,
            ScanResult::TYPE_SKIPPED,
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

    public static function error(string $path, ?string $error = null): self
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

    public function __toString(): string
    {
        $type = match ($this->type) {
            self::TYPE_SUCCESS => 'Success',
            self::TYPE_ERROR => 'Error',
            self::TYPE_SKIPPED => 'Skipped',
            default => throw new Exception('Invalid type'),
        };

        $str = $type . ': ' . $this->path;

        return $this->isError() ? $str . ' - ' . $this->error : $str;
    }
}
