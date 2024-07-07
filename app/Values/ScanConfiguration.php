<?php

namespace App\Values;

use App\Models\User;

final class ScanConfiguration
{
    /**
     * @param User $owner The user who owns the song
     * @param bool $makePublic Whether to make the song public
     * @param array<string> $ignores The tags to ignore/exclude (only taken into account if the song already exists)
     * @param bool $force Whether to force syncing, even if the file is unchanged
     */
    private function __construct(public User $owner, public bool $makePublic, public array $ignores, public bool $force)
    {
    }

    public static function make(
        User $owner,
        bool $makePublic = false,
        array $ignores = [],
        bool $force = false
    ): self {
        return new self($owner, $makePublic, $ignores, $force);
    }
}
