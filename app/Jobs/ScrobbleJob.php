<?php

namespace App\Jobs;

use App\Models\Song;
use App\Models\User;
use App\Services\ScrobbleService;

class ScrobbleJob extends QueuedJob
{
    public function __construct(
        public User $user,
        public Song $song,
        public int $timestamp,
    ) {}

    public function handle(ScrobbleService $scrobbleService): void
    {
        $scrobbleService->scrobble($this->song, $this->user, $this->timestamp);
    }
}
