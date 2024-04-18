<?php

namespace App\Values;

use App\Enums\ScanResultType;

final class ScanResult
{
    private function __construct(
        public string $path,
        public ScanResultType $type,
        public ?string $error = null
    ) {
    }

    public static function success(string $path): self
    {
        return new self($path, ScanResultType::SUCCESS, null);
    }

    public static function skipped(string $path): self
    {
        return new self($path, ScanResultType::SKIPPED, null);
    }

    public static function error(string $path, ?string $error = null): self
    {
        return new self($path, ScanResultType::ERROR, $error);
    }

    public function isSuccess(): bool
    {
        return $this->type === ScanResultType::SUCCESS;
    }

    public function isSkipped(): bool
    {
        return $this->type === ScanResultType::SKIPPED;
    }

    public function isError(): bool
    {
        return $this->type === ScanResultType::ERROR;
    }

    public function isValid(): bool
    {
        return $this->isSuccess() || $this->isSkipped();
    }

    public function __toString(): string
    {
        $name = $this->type->value . ': ' . $this->path;

        return $this->isError() ? $name . ' - ' . $this->error : $name;
    }
}
