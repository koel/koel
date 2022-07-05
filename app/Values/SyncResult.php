<?php

namespace App\Values;

use Illuminate\Support\Collection;

final class SyncResult
{
    private function __construct(public Collection $success, public Collection $bad, public Collection $unmodified)
    {
    }

    public static function init(): self
    {
        return new self(collect(), collect(), collect());
    }

    /** @return Collection|array<string> */
    public function validEntries(): Collection
    {
        return $this->success->merge($this->unmodified);
    }
}
