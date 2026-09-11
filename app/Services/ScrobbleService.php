<?php

namespace App\Services;

use App\Models\Song;
use App\Models\User;
use App\Services\Contracts\Scrobbler;
use App\Services\Integrations\LastfmService;
use App\Services\Integrations\ListenBrainzService;

class ScrobbleService
{
    /** @var array<Scrobbler> */
    private array $scrobblers;

    public function __construct(LastfmService $lastfm, ListenBrainzService $listenbrainz)
    {
        $this->scrobblers = [$lastfm, $listenbrainz];
    }

    public function scrobble(Song $song, User $user, int $timestamp): void
    {
        foreach ($this->connectedScrobblers($user) as $scrobbler) {
            $scrobbler->scrobble($song, $user, $timestamp);
        }
    }

    public function updateNowPlaying(Song $song, User $user): void
    {
        foreach ($this->connectedScrobblers($user) as $scrobbler) {
            $scrobbler->updateNowPlaying($song, $user);
        }
    }

    public function hasConnectedScrobbler(User $user): bool
    {
        return (bool) $this->connectedScrobblers($user);
    }

    /** @return array<Scrobbler> */
    private function connectedScrobblers(User $user): array
    {
        return array_values(array_filter(
            $this->scrobblers,
            static fn (Scrobbler $scrobbler): bool => $scrobbler->isConnected($user),
        ));
    }
}
