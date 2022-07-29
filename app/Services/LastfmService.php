<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\User;
use App\Values\AlbumInformation;
use App\Values\ArtistInformation;
use App\Values\LastfmLoveTrackParameters;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\Utils;
use Illuminate\Support\Collection;
use Throwable;

class LastfmService extends ApiClient implements ApiConsumerInterface
{
    /**
     * Override the key param, since, again, Last.fm wants to be different.
     */
    protected string $keyParam = 'api_key';

    /**
     * Determine if our application is using Last.fm.
     */
    public function used(): bool
    {
        return (bool) $this->getKey();
    }

    /**
     * Determine if Last.fm integration is enabled.
     */
    public function enabled(): bool
    {
        return $this->getKey() && $this->getSecret();
    }

    public function getArtistInformation(Artist $artist): ?ArtistInformation
    {
        if (!$this->enabled()) {
            return null;
        }

        $name = urlencode($artist->name);

        try {
            return $this->cache->remember(
                md5("lastfm_artist_$name"),
                now()->addWeek(),
                function () use ($name): ?ArtistInformation {
                    $response = $this->get("?method=artist.getInfo&autocorrect=1&artist=$name&format=json");

                    return $response?->artist ? ArtistInformation::fromLastFmData($response->artist) : null;
                }
            );
        } catch (Throwable $e) {
            $this->logger->error($e);

            return null;
        }
    }

    public function getAlbumInformation(Album $album): ?AlbumInformation
    {
        if (!$this->enabled()) {
            return null;
        }

        $albumName = urlencode($album->name);
        $artistName = urlencode($album->artist->name);

        try {
            $cacheKey = md5("lastfm_album_{$albumName}_{$artistName}");

            return $this->cache->remember(
                $cacheKey,
                now()->addWeek(),
                function () use ($albumName, $artistName): ?AlbumInformation {
                    $response = $this
                        ->get("?method=album.getInfo&autocorrect=1&album=$albumName&artist=$artistName&format=json");

                    return $response?->album ? AlbumInformation::fromLastFmData($response->album) : null;
                }
            );
        } catch (Throwable $e) {
            $this->logger->error($e);

            return null;
        }
    }

    /**
     * Get Last.fm's session key for the authenticated user using a token.
     *
     * @param string $token The token after successfully connecting to Last.fm
     *
     * @see http://www.last.fm/api/webauth#4
     */
    public function getSessionKey(string $token): ?string
    {
        $query = $this->buildAuthCallParams([
            'method' => 'auth.getSession',
            'token' => $token,
        ], true);

        try {
            return $this->get("/?$query&format=json", [], false)->session->key;
        } catch (Throwable $e) {
            $this->logger->error($e);

            return null;
        }
    }

    public function scrobble(
        string $artistName,
        string $trackName,
        $timestamp,
        string $albumName,
        string $sessionKey
    ): void {
        $params = [
            'artist' => $artistName,
            'track' => $trackName,
            'timestamp' => $timestamp,
            'sk' => $sessionKey,
            'method' => 'track.scrobble',
        ];

        if ($albumName) {
            $params['album'] = $albumName;
        }

        try {
            $this->post('/', $this->buildAuthCallParams($params), false);
        } catch (Throwable $e) {
            $this->logger->error($e);
        }
    }

    public function toggleLoveTrack(LastfmLoveTrackParameters $params, string $sessionKey, bool $love = true): void
    {
        try {
            $this->post('/', $this->buildAuthCallParams([
                'track' => $params->trackName,
                'artist' => $params->artistName,
                'sk' => $sessionKey,
                'method' => $love ? 'track.love' : 'track.unlove',
            ]), false);
        } catch (Throwable $e) {
            $this->logger->error($e);
        }
    }

    /**
     * @param Collection|array<LastfmLoveTrackParameters> $parameterCollection
     */
    public function batchToggleLoveTracks(Collection $parameterCollection, string $sessionKey, bool $love = true): void
    {
        $promises = $parameterCollection->map(
            fn (LastfmLoveTrackParameters $params): Promise => $this->postAsync('/', $this->buildAuthCallParams([
                'track' => $params->trackName,
                'artist' => $params->artistName,
                'sk' => $sessionKey,
                'method' => $love ? 'track.love' : 'track.unlove',
            ]), false)
        );

        try {
            Utils::unwrap($promises);
        } catch (Throwable $e) {
            $this->logger->error($e);
        }
    }

    public function updateNowPlaying(
        string $artistName,
        string $trackName,
        string $albumName,
        int|float $duration,
        string $sessionKey
    ): void {
        $params = [
            'artist' => $artistName,
            'track' => $trackName,
            'duration' => $duration,
            'sk' => $sessionKey,
            'method' => 'track.updateNowPlaying',
        ];

        if ($albumName) {
            $params['album'] = $albumName;
        }

        try {
            $this->post('/', $this->buildAuthCallParams($params), false);
        } catch (Throwable $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Build the parameters to use for _authenticated_ Last.fm API calls.
     * Such calls require:
     * - The API key (api_key)
     * - The API signature (api_sig).
     *
     * @see http://www.last.fm/api/webauth#5
     *
     * @param array $params The array of parameters
     * @param bool $toString Whether to turn the array into a query string
     *
     * @return array<mixed>|string
     */
    public function buildAuthCallParams(array $params, bool $toString = false): array|string
    {
        $params['api_key'] = $this->getKey();
        ksort($params);

        // Generate the API signature.
        // @link http://www.last.fm/api/webauth#6
        $str = '';

        foreach ($params as $name => $value) {
            $str .= $name . $value;
        }

        $str .= $this->getSecret();
        $params['api_sig'] = md5($str);

        if (!$toString) {
            return $params;
        }

        $query = '';

        foreach ($params as $key => $value) {
            $query .= "$key=$value&";
        }

        return rtrim($query, '&');
    }

    public function getKey(): ?string
    {
        return config('koel.lastfm.key');
    }

    public function getEndpoint(): ?string
    {
        return config('koel.lastfm.endpoint');
    }

    public function getSecret(): ?string
    {
        return config('koel.lastfm.secret');
    }

    public function setUserSessionKey(User $user, ?string $sessionKey): void
    {
        $user->preferences->lastFmSessionKey = $sessionKey;
        $user->save();
    }
}
