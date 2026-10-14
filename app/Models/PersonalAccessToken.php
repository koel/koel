<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Laravel\Sanctum\PersonalAccessToken as BasePersonalAccessToken;

class PersonalAccessToken extends BasePersonalAccessToken
{
    private const int LAST_USED_AT_UPDATE_INTERVAL_IN_SECONDS = 60;

    /** @inheritdoc */
    public function save(array $options = []): bool
    {
        if ($this->isOnlyRefreshingRecentLastUsedAt()) {
            return true;
        }

        return parent::save($options);
    }

    private function isOnlyRefreshingRecentLastUsedAt(): bool
    {
        /** @var ?CarbonInterface $storedLastUsedAt */
        $storedLastUsedAt = $this->getOriginal('last_used_at');

        return (
            $this->exists
            && array_keys($this->getDirty()) === ['last_used_at']
            && $storedLastUsedAt?->isAfter(now()->subSeconds(self::LAST_USED_AT_UPDATE_INTERVAL_IN_SECONDS))
        );
    }
}
