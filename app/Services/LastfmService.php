<?php

namespace App\Services;

use Exception;

class LastfmService extends ApiClient implements ApiConsumerInterface
{
    /**
     * Specify the response format, since Last.fm only returns XML.
     *
     * @var string
     */
    protected $responseFormat = 'xml';

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
                $response = $this->get("?method=artist.getInfo&autocorrect=1&artist=$name");

                if (!$response) {
                    return null;
                }

                $response = simplexml_load_string($response->asXML());
                $response = json_decode(json_encode($response), true);

                if (!$response || !$artist = array_get($response, 'artist')) {
                    return null;
                }

                return $this->buildArtistInformation($artist);
            });
        } catch (Exception $e) {
            $this->logger->error($e);

            return null;
        }
    }

    /**
     * Build a Koel-usable array of artist information using the data from Last.fm.
     *
     * @param mixed[] $artistData
     *
     * @return mixed[]
     */
    private function buildArtistInformation(array $artistData): array
    {
        return [
            'url' => array_get($artistData, 'url'),
            'image' => count($artistData['image']) > 3 ? $artistData['image'][3] : $artistData['image'][0],
            'bio' => [
                'summary' => $this->formatText(array_get($artistData, 'bio.summary', '')),
                'full' => $this->formatText(array_get($artistData, 'bio.content', '')),
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
                $response = $this->get("?method=album.getInfo&autocorrect=1&album=$albumName&artist=$artistName");

                if (!$response) {
                    return null;
                }

                $response = simplexml_load_string($response->asXML());
                $response = json_decode(json_encode($response), true);

                if (!$response || !$album = array_get($response, 'album')) {
                    return null;
                }

                return $this->buildAlbumInformation($album);
            });
        } catch (Exception $e) {
            $this->logger->error($e);

            return null;
        }
    }

    /**
     * Build a Koel-usable array of album information using the data from Last.fm.
     *
     * @param mixed[] $albumData
     *
     * @return mixed[]
     */
    private function buildAlbumInformation(array $albumData): array
    {
        return [
            'url' => array_get($albumData, 'url'),
            'image' => count($albumData['image']) > 3 ? $albumData['image'][3] : $albumData['image'][0],
            'wiki' => [
                'summary' => $this->formatText(array_get($albumData, 'wiki.summary', '')),
                'full' => $this->formatText(array_get($albumData, 'wiki.content', '')),
            ],
            'tracks' => array_map(function ($track) {
                return [
                    'title' => $track['name'],
                    'length' => (int) $track['duration'],
                    'url' => $track['url'],
                ];
            }, array_get($albumData, 'tracks.track', [])),
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
            return (string) $this->get("/?$query", [], false)->session->key;
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
     * Correctly format a string returned by Last.fm.
     */
    protected function formatText(string $str): string
    {
        if (!$str) {
            return '';
        }

        return trim(str_replace('Read more on Last.fm', '', nl2br(strip_tags(html_entity_decode($str)))));
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
