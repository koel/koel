<?php

namespace App\Models\Concerns;

trait HasMbid
{
    /**
     * Claim the identifier only if the record doesn't have one yet, in a single statement so that the first file
     * to carry it wins regardless of how many other files reference the same album or artist during a scan.
     */
    public function setMbidIfMissing(?string $mbid): void
    {
        if (!$mbid) {
            return;
        }

        static::query()->whereKey($this->getKey())->whereNull('mbid')->update(['mbid' => $mbid]);
    }
}
