<?php

namespace App\Models\Concerns;

trait HasMbid
{
    public function setMbidIfMissing(?string $mbid): void
    {
        if (!$mbid || $this->mbid) {
            return;
        }

        // A parallel scan runs several processes, each with its own cached copy of this record. Two of them can both
        // see no identifier in memory, so the database must only accept a value when the column is still empty.
        $stored = static::query()->whereKey($this->getKey())->whereNull('mbid')->update(['mbid' => $mbid]) > 0;

        if ($stored) {
            $this->mbid = $mbid;
            $this->syncOriginalAttribute('mbid');
        }
    }
}
