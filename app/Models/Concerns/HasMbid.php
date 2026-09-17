<?php

namespace App\Models\Concerns;

trait HasMbid
{
    public function setMbidIfMissing(?string $mbid): void
    {
        if (!$mbid) {
            return;
        }

        $wasMissing = static::query()->whereKey($this->getKey())->whereNull('mbid')->update(['mbid' => $mbid]) > 0;

        if ($wasMissing) {
            $this->mbid = $mbid;
            $this->syncOriginalAttribute('mbid');
        }
    }
}
