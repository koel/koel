<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Log;

class Lastfm extends RESTfulService
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
     * Construct an instance of Lastfm service.
     *
     * @param string $key    Last.fm API key.
     * @param string $secret Last.fm API shared secret.
     * @param Client $client The Guzzle HTTP client.
     */
    public function __construct($key = null, $secret = null, Client $client = null)
    {
        parent::__construct(
            $key ?: config('koel.lastfm.key'),
            $secret ?: config('koel.lastfm.secret'),
            'https://ws.audioscrobbler.com/2.0',
            $client ?: new Client()
        );
    }

    /**
     * Determine if our application is using Last.fm.
     *
     * @return bool
     */
    public function used()
    {
        return config('koel.lastfm.key') && config('koel.lastfm.secret');
    }

    /**
     * Determine if Last.fm integration is enabled.
     *
     * @return bool
     */
    public function enabled()
    {
        return $this->getKey() && $this->getSecret();
    }

    /**
     * Get information about an artist.
     *
     * @param $name string Name of the artist
     *
     * @return array|false
     */
    public function getArtistInfo($name)
    {
        if (!$this->enabled()) {
            return false;
        }

        $name = urlencode($name);

        try {
            $cacheKey = md5("lastfm_artist_$name");

            if ($response = cache($cacheKey)) {
                $response = simplexml_load_string($response);
            } else {
                if ($response = $this->get("?method=artist.getInfo&autocorrect=1&artist=$name")) {
                    cache([$cacheKey => $response->asXML()], 24 * 60 * 7);
                }
            }

            $response = json_decode(json_encode($response), true);

            if (!$response || !$artist = array_get($response, 'artist')) {
                return false;
            }

            return $this->buildArtistInfo($artist);
        } catch (Exception $e) {
            Log::error($e);

            return false;
        }
    }

    /**
     * Build a Koel-usable array of artist information using the data from Last.fm.
     *
     * @param array $lastfmArtist
     *
     * @return array
     */
    private function buildArtistInfo(array $lastfmArtist)
    {
        return [
            'url' => array_get($lastfmArtist, 'url'),
            'image' => count($lastfmArtist['image']) > 3 ? $lastfmArtist['image'][3] : $lastfmArtist['image'][0],
            'bio' => [
                'summary' => $this->formatText(array_get($lastfmArtist, 'bio.summary')),
                'full' => $this->formatText(array_get($lastfmArtist, 'bio.content')),
            ],
        ];
    }

    /**
     * Get information about an album.
     *
     * @param string $name       Name of the album
     * @param string $artistName Name of the artist
     *
     * @return array|false
     */
    public function getAlbumInfo($name, $artistName)
    {
        if (!$this->enabled()) {
            return false;
        }

        $name = urlencode($name);
        $artistName = urlencode($artistName);

        try {
            $cacheKey = md5("lastfm_album_{$name}_{$artistName}");

            if ($response = cache($cacheKey)) {
                $response = simplexml_load_string($response);
            } else {
                if ($response = $this->get("?method=album.getInfo&autocorrect=1&album=$name&artist=$artistName")) {
                    cache([$cacheKey => $response->asXML()], 24 * 60 * 7);
                }
            }

            $response = json_decode(json_encode($response), true);

            if (!$response || !$album = array_get($response, 'album')) {
                return false;
            }

            return $this->buildAlbumInfo($album);
        } catch (Exception $e) {
            Log::error($e);

            return false;
        }
    }

    /**
     * Build a Koel-usable array of album information using the data from Last.fm.
     *
     * @param array $lastfmAlbum
     *
     * @return array
     */
    private function buildAlbumInfo(array $lastfmAlbum)
    {
        return [
            'url' => array_get($lastfmAlbum, 'url'),
            'image' => count($lastfmAlbum['image']) > 3 ? $lastfmAlbum['image'][3] : $lastfmAlbum['image'][0],
            'wiki' => [
                'summary' => $this->formatText(array_get($lastfmAlbum, 'wiki.summary')),
                'full' => $this->formatText(array_get($lastfmAlbum, 'wiki.content')),
            ],
            'tracks' => array_map(function ($track) {
                return [
                    'title' => $track['name'],
                    'length' => (int) $track['duration'],
                    'url' => $track['url'],
                ];
            }, array_get($lastfmAlbum, 'tracks.track', [])),
        ];
    }

    /**
     * Get Last.fm's session key for the authenticated user using a token.
     *
     * @param string $token The token after successfully connecting to Last.fm
     *
     * @link http://www.last.fm/api/webauth#4
     *
     * @return string The token key
     */
    public function getSessionKey($token)
    {
        $query = $this->buildAuthCallParams([
            'method' => 'auth.getSession',
            'token' => $token,
        ], true);

        try {
            return (string) $this->get("/?$query", [], false)->session->key;
        } catch (Exception $e) {
            Log::error($e);

            return false;
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
     *
     * @return bool
     */
    public function scrobble($artist, $track, $timestamp, $album, $sk)
    {
        $params = compact('artist', 'track', 'timestamp', 'sk');

        if ($album) {
            $params['album'] = $album;
        }

        $params['method'] = 'track.scrobble';

        try {
            return (bool) $this->post('/', $this->buildAuthCallParams($params), false);
        } catch (Exception $e) {
            Log::error($e);

            return false;
        }
    }

    /**
     * Love or unlove a track on Last.fm.
     *
     * @param string $track  The track name
     * @param string $artist The artist's name
     * @param string $sk     The session key
     * @param bool   $love   Whether to love or unlove. Such cheesy terms... urrgggh
     *
     * @return bool
     */
    public function toggleLoveTrack($track, $artist, $sk, $love = true)
    {
        $params = compact('track', 'artist', 'sk');
        $params['method'] = $love ? 'track.love' : 'track.unlove';

        try {
            return (bool) $this->post('/', $this->buildAuthCallParams($params), false);
        } catch (Exception $e) {
            Log::error($e);

            return false;
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
     *
     * @return bool
     */
    public function updateNowPlaying($artist, $track, $album, $duration, $sk)
    {
        $params = compact('artist', 'track', 'duration', 'sk');
        $params['method'] = 'track.updateNowPlaying';

        if ($album) {
            $params['album'] = $album;
        }

        try {
            return (bool) $this->post('/', $this->buildAuthCallParams($params), false);
        } catch (Exception $e) {
            Log::error($e);

            return false;
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
    public function buildAuthCallParams(array $params, $toString = false)
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
     *
     * @param string $str
     *
     * @return string
     */
    protected function formatText($str)
    {
        if (!$str) {
            return '';
        }

        return trim(str_replace('Read more on Last.fm', '', nl2br(strip_tags(html_entity_decode($str)))));
    }
}
