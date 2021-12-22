<?php

namespace App\Values;

use Illuminate\Support\Collection;

final class SyncResult
{
    /** @var Collection|array<string> */
    public Collection $success;

    /** @var Collection|array<string> */
    public Collection $bad;

    /** @var Collection|array<string> */
    public Collection $unmodified;

    private function __construct(Collection $success, Collection $bad, Collection $unmodified)
    {
        $this->success = $success;
        $this->bad = $bad;
        $this->unmodified = $unmodified;
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
