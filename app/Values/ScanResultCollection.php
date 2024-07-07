<?php

namespace App\Values;

use Illuminate\Support\Collection;

final class ScanResultCollection extends Collection
{
    public static function create(): self
    {
        return new self();
    }

    /** @return Collection<array-key, ScanResult> */
    public function valid(): Collection
    {
        return $this->filter(static fn (ScanResult $result): bool => $result->isValid());
    }

    /** @return Collection<array-key, ScanResult> */
    public function success(): Collection
    {
        return $this->filter(static fn (ScanResult $result): bool => $result->isSuccess());
    }

    /** @return Collection<array-key, ScanResult> */
    public function skipped(): Collection
    {
        return $this->filter(static fn (ScanResult $result): bool => $result->isSkipped());
    }

    /** @return Collection<array-key, ScanResult> */
    public function error(): Collection
    {
        return $this->filter(static fn (ScanResult $result): bool => $result->isError());
    }
}
