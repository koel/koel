<?php

namespace App\Services;

use App\Values\LastfmLoveTrackParameters;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\Utils;
use Illuminate\Support\Collection;
use Throwable;

class LastfmService extends AbstractApiClient implements ApiConsumerInterface
{
    /**
     * Override the key param, since, again, Last.fm wants to be different.
     */
    protected $keyParam = 'api_key';

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

    /** @return array<mixed>|null */
    public function getArtistInformation(string $name): ?array
    {
        if (!$this->enabled()) {
            return null;
        }

        $name = urlencode($name);

        try {
            return $this->cache->remember(md5("lastfm_artist_$name"), 24 * 60 * 7, function () use ($name): ?array {
                $response = $this->get("?method=artist.getInfo&autocorrect=1&artist=$name&format=json");

                if (!$response || !isset($response->artist)) {
                    return null;
                }

                return $this->buildArtistInformation($response->artist);
            });
        } catch (Throwable $e) {
            $this->logger->error($e);

            return null;
        }
    }

    /**
     * Build a Koel-usable array of artist information using the data from Last.fm.
     *
     * @param mixed $data
     *
     * @return array<mixed>
     */
    private function buildArtistInformation($data): array
    {
        return [
            'url' => $data->url,
            'image' => count($data->image) > 3 ? $data->image[3]->{'#text'} : $data->image[0]->{'#text'},
            'bio' => [
                'summary' => isset($data->bio) ? $this->formatText($data->bio->summary) : '',
                'full' => isset($data->bio) ? $this->formatText($data->bio->content) : '',
            ],
        ];
    }

    /** @return array<mixed>|null */
    public function getAlbumInformation(string $albumName, string $artistName): ?array
    {
        if (!$this->enabled()) {
            return null;
        }

        $albumName = urlencode($albumName);
        $artistName = urlencode($artistName);

        try {
            $cacheKey = md5("lastfm_album_{$albumName}_{$artistName}");

            return $this->cache->remember($cacheKey, 24 * 60 * 7, function () use ($albumName, $artistName): ?array {
                $response = $this
                    ->get("?method=album.getInfo&autocorrect=1&album=$albumName&artist=$artistName&format=json");

                if (!$response || !isset($response->album)) {
                    return null;
                }

                return $this->buildAlbumInformation($response->album);
            });
        } catch (Throwable $e) {
            $this->logger->error($e);

            return null;
        }
    }

    /**
     * Build a Koel-usable array of album information using the data from Last.fm.
     *
     * @param mixed $data
     *
     * @return array<mixed>
     */
    private function buildAlbumInformation($data): array
    {
        return [
            'url' => $data->url,
            'image' => count($data->image) > 3 ? $data->image[3]->{'#text'} : $data->image[0]->{'#text'},
            'wiki' => [
                'summary' => isset($data->wiki) ? $this->formatText($data->wiki->summary) : '',
                'full' => isset($data->wiki) ? $this->formatText($data->wiki->content) : '',
            ],
            'tracks' => array_map(static function ($track): array {
                return [
                    'title' => $track->name,
                    'length' => (int) $track->duration,
                    'url' => $track->url,
                ];
            }, isset($data->tracks) ? $data->tracks->track : []),
        ];
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
                'track' => $params->getTrackName(),
                'artist' => $params->getArtistName(),
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
            function (LastfmLoveTrackParameters $params) use ($sessionKey, $love): Promise {
                return $this->postAsync('/', $this->buildAuthCallParams([
                    'track' => $params->getTrackName(),
                    'artist' => $params->getArtistName(),
                    'sk' => $sessionKey,
                    'method' => $love ? 'track.love' : 'track.unlove',
                ]), false);
            }
        );

        try {
            Utils::unwrap($promises);
        } catch (Throwable $e) {
            $this->logger->error($e);
        }
    }

    /**
     * @param int|float $duration Duration of the track, in seconds
     */
    public function updateNowPlaying(
        string $artistName,
        string $trackName,
        string $albumName,
        $duration,
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
    public function buildAuthCallParams(array $params, bool $toString = false)
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

    /**
     * Correctly format a value returned by Last.fm.
     */
    protected function formatText(?string $value): string
    {
        if (!$value) {
            return '';
        }

        return trim(str_replace('Read more on Last.fm', '', nl2br(strip_tags(html_entity_decode($value)))));
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
}
