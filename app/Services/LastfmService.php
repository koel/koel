<?php

namespace App\Services;

use Exception;

class LastfmService extends AbstractApiClient implements ApiConsumerInterface
{
    /**
     * Override the key param, since, again, Lastfm wants to be different.
     *
     * @var string
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

    /**
     * Get information about an artist.
     *
     * @param string $name Name of the artist
     *
     * @return mixed[]|null
     */
    public function getArtistInformation(string $name): ?array
    {
        if (!$this->enabled()) {
            return null;
        }

        $name = urlencode($name);

        try {
            return $this->cache->remember(md5("lastfm_artist_$name"), 24 * 60 * 7, function () use ($name): ?array {
                $response = $this->get("?method=artist.getInfo&autocorrect=1&artist=$name&format=json");

                if (!$response || !$response->artist) {
                    return null;
                }

                return $this->buildArtistInformation($response->artist);
            });
        } catch (Exception $e) {
            $this->logger->error($e);

            return null;
        }
    }

    /**
     * Build a Koel-usable array of artist information using the data from Last.fm.
     *
     * @param object $data
     *
     * @return mixed[]
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

    /**
     * Get information about an album.
     *
     * @return mixed[]|null
     */
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

                if (!$response || !$response->album) {
                    return null;
                }

                return $this->buildAlbumInformation($response->album);
            });
        } catch (Exception $e) {
            $this->logger->error($e);

            return null;
        }
    }

    /**
     * Build a Koel-usable array of album information using the data from Last.fm.
     *
     * @param object $data
     *
     * @return mixed[]
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
     * @link http://www.last.fm/api/webauth#4
     */
    public function getSessionKey(string $token): ?string
    {
        $query = $this->buildAuthCallParams([
            'method' => 'auth.getSession',
            'token' => $token,
        ], true);

        try {
            return $this->get("/?$query&format=json", [], false)->session->key;
        } catch (Exception $e) {
            $this->logger->error($e);

            return null;
        }
    }

    /**
     * Scrobble a song.
     *
     * @param string     $artist    The artist name
     * @param string     $track     The track name
     * @param string|int $timestamp The UNIX timestamp
     * @param string     $album     The album name
     * @param string     $sk        The session key
     */
    public function scrobble(string $artist, string $track, $timestamp, string $album, string $sk): void
    {
        $params = compact('artist', 'track', 'timestamp', 'sk');

        if ($album) {
            $params['album'] = $album;
        }

        $params['method'] = 'track.scrobble';

        try {
            $this->post('/', $this->buildAuthCallParams($params), false);
        } catch (Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Love or unlove a track on Last.fm.
     *
     * @param string $track  The track name
     * @param string $artist The artist's name
     * @param string $sk     The session key
     * @param bool   $love   Whether to love or unlove. Such cheesy terms... urrgggh
     */
    public function toggleLoveTrack(string $track, string $artist, string $sk, ?bool $love = true): void
    {
        $params = compact('track', 'artist', 'sk');
        $params['method'] = $love ? 'track.love' : 'track.unlove';

        try {
            $this->post('/', $this->buildAuthCallParams($params), false);
        } catch (Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Update a track's "now playing" on Last.fm.
     *
     * @param string    $artist   Name of the artist
     * @param string    $track    Name of the track
     * @param string    $album    Name of the album
     * @param int|float $duration Duration of the track, in seconds
     * @param string    $sk       The session key
     */
    public function updateNowPlaying(string $artist, string $track, string $album, $duration, string $sk): void
    {
        $params = compact('artist', 'track', 'duration', 'sk');
        $params['method'] = 'track.updateNowPlaying';

        if ($album) {
            $params['album'] = $album;
        }

        try {
            $this->post('/', $this->buildAuthCallParams($params), false);
        } catch (Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Build the parameters to use for _authenticated_ Last.fm API calls.
     * Such calls require:
     * - The API key (api_key)
     * - The API signature (api_sig).
     *
     * @link http://www.last.fm/api/webauth#5
     *
     * @param array $params   The array of parameters.
     * @param bool  $toString Whether to turn the array into a query string
     *
     * @return array|string
     */
    public function buildAuthCallParams(array $params, bool $toString = false)
    {
        $params['api_key'] = $this->getKey();
        ksort($params);

        // Generate the API signature.
        // @link http://www.last.fm/api/webauth#6
        $str = '';

        foreach ($params as $name => $value) {
            $str .= $name.$value;
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
     *
     * @param string|array $value
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
