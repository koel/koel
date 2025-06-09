<?php

namespace App\Jobs;

use App\Models\Song;
use App\Models\User;
use App\Services\LastfmService;

class ScrobbleJob extends QueuedJob
{
    public function __construct(public User $user, public Song $song, public int $timestamp)
    {
    }

    public function handle(LastfmService $lastfmService): void
    {
        $lastfmService->scrobble($this->song, $this->user, $this->timestamp);
    }
}
