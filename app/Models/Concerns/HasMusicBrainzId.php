<?php

namespace App\Models\Concerns;

trait HasMusicBrainzId
{
    /**
     * Claim the identifier only if the record doesn't have one yet, in a single statement so that the first file
     * to carry it wins regardless of how many other files reference the same album or artist during a scan.
     */
    public function fillMissingMbid(?string $mbid): void
    {
        if (!$mbid) {
            return;
        }

        static::query()->whereKey($this->getKey())->whereNull('mbid')->update(['mbid' => $mbid]);
    }
}
