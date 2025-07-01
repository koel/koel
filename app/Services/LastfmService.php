<?php

namespace App\Services;

use App\Http\Integrations\Lastfm\LastfmConnector;
use App\Http\Integrations\Lastfm\Requests\GetAlbumInfoRequest;
use App\Http\Integrations\Lastfm\Requests\GetArtistInfoRequest;
use App\Http\Integrations\Lastfm\Requests\GetSessionKeyRequest;
use App\Http\Integrations\Lastfm\Requests\ScrobbleRequest;
use App\Http\Integrations\Lastfm\Requests\ToggleLoveTrackRequest;
use App\Http\Integrations\Lastfm\Requests\UpdateNowPlayingRequest;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use App\Services\Contracts\Encyclopedia;
use App\Values\AlbumInformation;
use App\Values\ArtistInformation;
use Generator;
use Illuminate\Support\Collection;

class LastfmService implements Encyclopedia
{
    public function __construct(private readonly LastfmConnector $connector)
    {
    }

    /**
     * Determine if our application is using Last.fm.
     */
    public static function used(): bool
    {
        return (bool) config('koel.services.lastfm.key');
    }

    /**
     * Determine if Last.fm integration is enabled.
     */
    public static function enabled(): bool
    {
        return config('koel.services.lastfm.key') && config('koel.services.lastfm.secret');
    }

    public function getArtistInformation(Artist $artist): ?ArtistInformation
    {
        if ($artist->is_unknown || $artist->is_various) {
            return null;
        }

        return rescue_if(static::enabled(), function () use ($artist): ?ArtistInformation {
            return $this->connector->send(new GetArtistInfoRequest($artist))->dto();
        });
    }

    public function getAlbumInformation(Album $album): ?AlbumInformation
    {
        if ($album->is_unknown || $album->artist->is_unknown) {
            return null;
        }

        return rescue_if(static::enabled(), function () use ($album): ?AlbumInformation {
            return $this->connector->send(new GetAlbumInfoRequest($album))->dto();
        });
    }

    public function scrobble(Song $song, User $user, int $timestamp): void
    {
        rescue(fn () => $this->connector->send(new ScrobbleRequest($song, $user, $timestamp)));
    }

    public function toggleLoveTrack(Song $song, User $user, bool $love): void
    {
        rescue(fn () => $this->connector->send(new ToggleLoveTrackRequest($song, $user, $love)));
    }

    /**
     * @param Collection<array-key, Song> $songs
     */
    public function batchToggleLoveTracks(Collection $songs, User $user, bool $love): void
    {
        $generatorCallback = static function () use ($songs, $user, $love): Generator {
            foreach ($songs as $song) {
                yield new ToggleLoveTrackRequest($song, $user, $love);
            }
        };

        $this->connector
            ->pool($generatorCallback)
            ->send()
            ->wait();
    }

    public function updateNowPlaying(Song $song, User $user): void
    {
        rescue(fn () => $this->connector->send(new UpdateNowPlayingRequest($song, $user)));
    }

    public function getSessionKey(string $token): ?string
    {
        return object_get($this->connector->send(new GetSessionKeyRequest($token))->object(), 'session.key');
    }

    public function setUserSessionKey(User $user, ?string $sessionKey): void
    {
        $user->preferences->lastFmSessionKey = $sessionKey;
        $user->save();
    }
}
