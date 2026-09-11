<?php

namespace App\Services;

use App\Models\Song;
use App\Models\User;
use App\Services\Contracts\Scrobbler;
use App\Services\Integrations\LastfmService;
use App\Services\Integrations\ListenBrainzService;
use Illuminate\Support\Arr;

class ScrobbleService
{
    /** @var array<Scrobbler> */
    private array $scrobblers;

    public function __construct(LastfmService $lastfm, ListenBrainzService $listenBrainz)
    {
        $this->scrobblers = [$lastfm, $listenBrainz];
    }

    public function scrobble(Song $song, User $user, int $timestamp): void
    {
        foreach ($this->getConnectedScrobblers($user) as $scrobbler) {
            $scrobbler->scrobble($song, $user, $timestamp);
        }
    }

    public function updateNowPlaying(Song $song, User $user): void
    {
        foreach ($this->getConnectedScrobblers($user) as $scrobbler) {
            $scrobbler->updateNowPlaying($song, $user);
        }
    }

    public function hasConnectedScrobbler(User $user): bool
    {
        return (bool) $this->getConnectedScrobblers($user);
    }

    /** @return array<Scrobbler> */
    private function getConnectedScrobblers(User $user): array
    {
        return Arr::where($this->scrobblers, static fn (Scrobbler $scrobbler): bool => $scrobbler->isConnected($user));
    }
}
