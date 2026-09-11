<?php

namespace App\Services\Integrations;

use App\Enums\ListenType;
use App\Http\Integrations\Listenbrainz\ListenbrainzConnector;
use App\Http\Integrations\Listenbrainz\Requests\SubmitListensRequest;
use App\Http\Integrations\Listenbrainz\Requests\ValidateTokenRequest;
use App\Models\Song;
use App\Models\User;
use App\Services\Contracts\Scrobbler;
use SensitiveParameter;

class ListenbrainzService implements Scrobbler
{
    public function __construct(
        private readonly ListenbrainzConnector $connector,
    ) {}

    public function isConnected(User $user): bool
    {
        return (bool) $user->preferences->listenBrainzToken;
    }

    public function scrobble(Song $song, User $user, int $timestamp): void
    {
        rescue(fn () => $this->connector->send(new SubmitListensRequest($song, $user, ListenType::SINGLE, $timestamp)));
    }

    public function updateNowPlaying(Song $song, User $user): void
    {
        rescue(fn () => $this->connector->send(new SubmitListensRequest($song, $user, ListenType::PLAYING_NOW)));
    }

    /**
     * Determine whether the token is accepted by ListenBrainz, so that an invalid one is never stored.
     */
    public function validateToken(#[SensitiveParameter] string $token): bool
    {
        return rescue(
            fn (): bool => (bool) object_get(
                $this->connector->send(new ValidateTokenRequest($token))->object(),
                'valid',
            ),
            false,
        );
    }

    public function setUserToken(User $user, #[SensitiveParameter] ?string $token): void
    {
        $user->preferences->listenBrainzToken = $token;
        $user->save();
    }
}
