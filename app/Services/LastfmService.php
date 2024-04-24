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
use App\Services\Contracts\MusicEncyclopedia;
use App\Values\AlbumInformation;
use App\Values\ArtistInformation;
use Generator;
use Illuminate\Cache\Repository as Cache;
use Illuminate\Support\Collection;

class LastfmService implements MusicEncyclopedia
{
    public function __construct(private readonly LastfmConnector $connector, private readonly Cache $cache)
    {
    }

    /**
     * Determine if our application is using Last.fm.
     */
    public static function used(): bool
    {
        return (bool) config('koel.lastfm.key');
    }

    /**
     * Determine if Last.fm integration is enabled.
     */
    public static function enabled(): bool
    {
        return config('koel.lastfm.key') && config('koel.lastfm.secret');
    }

    public function getArtistInformation(Artist $artist): ?ArtistInformation
    {
        if ($artist->is_unknown || $artist->is_various) {
            return null;
        }

        return attempt_if(static::enabled(), function () use ($artist): ?ArtistInformation {
            return $this->cache->remember(
                "lastfm.artist.$artist->id",
                now()->addWeek(),
                fn () => $this->connector->send(new GetArtistInfoRequest($artist))->dto()
            );
        });
    }

    public function getAlbumInformation(Album $album): ?AlbumInformation
    {
        if ($album->is_unknown || $album->artist->is_unknown) {
            return null;
        }

        return attempt_if(static::enabled(), function () use ($album): ?AlbumInformation {
            return $this->cache->remember(
                "lastfm.album.$album->id",
                now()->addWeek(),
                fn () => $this->connector->send(new GetAlbumInfoRequest($album))->dto()
            );
        });
    }

    public function scrobble(Song $song, User $user, int $timestamp): void
    {
        attempt(fn () => $this->connector->send(new ScrobbleRequest($song, $user, $timestamp)));
    }

    public function toggleLoveTrack(Song $song, User $user, bool $love): void
    {
        attempt(fn () => $this->connector->send(new ToggleLoveTrackRequest($song, $user, $love)));
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
        attempt(fn () => $this->connector->send(new UpdateNowPlayingRequest($song, $user)));
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
