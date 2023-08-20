<?php

namespace App\DTO;

use Illuminate\Support\Collection;

final class SyncResultCollection extends Collection
{
    public static function create(): self
    {
        return new self();
    }

    /** @return Collection|array<array-key, SyncResult> */
    public function valid(): Collection
    {
        return $this->filter(static fn (SyncResult $result): bool => $result->isValid());
    }

    /** @return Collection|array<array-key, SyncResult> */
    public function success(): Collection
    {
        return $this->filter(static fn (SyncResult $result): bool => $result->isSuccess());
    }

    /** @return Collection|array<array-key, SyncResult> */
    public function skipped(): Collection
    {
        return $this->filter(static fn (SyncResult $result): bool => $result->isSkipped());
    }

    /** @return Collection|array<array-key, SyncResult> */
    public function error(): Collection
    {
        return $this->filter(static fn (SyncResult $result): bool => $result->isError());
    }
}
