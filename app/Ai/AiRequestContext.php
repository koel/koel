<?php

namespace App\Ai;

use App\Models\User;

class AiRequestContext
{
    public function __construct(
        public readonly User $user,
        public readonly ?string $currentSongId = null,
        public readonly ?string $currentRadioStationId = null,
    ) {}
}
