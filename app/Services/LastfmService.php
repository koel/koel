<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use App\Services\ApiClients\LastfmClient;
use App\Values\AlbumInformation;
use App\Values\ArtistInformation;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\Utils;
use Illuminate\Support\Collection;

class LastfmService implements MusicEncyclopedia
{
    public function __construct(private LastfmClient $client)
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
        return attempt_if(static::enabled(), function () use ($artist): ?ArtistInformation {
            $name = urlencode($artist->name);
            $response = $this->client->get("?method=artist.getInfo&autocorrect=1&artist=$name&format=json");

            return $response?->artist ? ArtistInformation::fromLastFmData($response->artist) : null;
        });
    }

    public function getAlbumInformation(Album $album): ?AlbumInformation
    {
        return attempt_if(static::enabled(), function () use ($album): ?AlbumInformation {
            $albumName = urlencode($album->name);
            $artistName = urlencode($album->artist->name);

            $response = $this->client
                ->get("?method=album.getInfo&autocorrect=1&album=$albumName&artist=$artistName&format=json");

            return $response?->album ? AlbumInformation::fromLastFmData($response->album) : null;
        });
    }

    public function scrobble(Song $song, User $user, int $timestamp): void
    {
        $params = [
            'artist' => $song->artist->name,
            'track' => $song->title,
            'timestamp' => $timestamp,
            'sk' => $user->lastfm_session_key,
            'method' => 'track.scrobble',
        ];

        if ($song->album->name !== Album::UNKNOWN_NAME) {
            $params['album'] = $song->album->name;
        }

        attempt(fn () => $this->client->post('/', $params, false));
    }

    public function toggleLoveTrack(Song $song, User $user, bool $love): void
    {
        attempt(fn () => $this->client->post('/', [
            'track' => $song->title,
            'artist' => $song->artist->name,
            'sk' => $user->lastfm_session_key,
            'method' => $love ? 'track.love' : 'track.unlove',
        ], false));
    }

    /**
     * @param Collection|array<array-key, Song> $songs
     */
    public function batchToggleLoveTracks(Collection $songs, User $user, bool $love): void
    {
        $promises = $songs->map(
            function (Song $song) use ($user, $love): Promise {
                return $this->client->postAsync('/', [
                    'track' => $song->title,
                    'artist' => $song->artist->name,
                    'sk' => $user->lastfm_session_key,
                    'method' => $love ? 'track.love' : 'track.unlove',
                ], false);
            }
        );

        attempt(static fn () => Utils::unwrap($promises));
    }

    public function updateNowPlaying(Song $song, User $user): void
    {
        $params = [
            'artist' => $song->artist->name,
            'track' => $song->title,
            'duration' => $song->length,
            'sk' => $user->lastfm_session_key,
            'method' => 'track.updateNowPlaying',
        ];

        if ($song->album->name !== Album::UNKNOWN_NAME) {
            $params['album'] = $song->album->name;
        }

        attempt(fn () => $this->client->post('/', $params, false));
    }

    public function getSessionKey(string $token): ?string
    {
        return $this->client->getSessionKey($token);
    }

    public function setUserSessionKey(User $user, ?string $sessionKey): void
    {
        $user->preferences->lastFmSessionKey = $sessionKey;
        $user->save();
    }
}
