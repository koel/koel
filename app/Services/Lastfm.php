<?php

namespace App\Services;

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
            $key ?: env('LASTFM_API_KEY'),
            $secret ?: env('LASTFM_API_SECRET'),
            'https://ws.audioscrobbler.com/2.0',
            $client ?: new Client()
        );
    }

    /**
     * Get information about an artist.
     * 
     * @param $name string Name of the artist
     *
     * @return object|false
     */
    public function getArtistInfo($name)
    {
        if (!$this->enabled()) {
            return false;
        }

        $name = urlencode($name);

        try {
            $cacheKey = md5("lastfm_artist_$name");

            if ($response = Cache::get($cacheKey)) {
                $response = simplexml_load_string($response);
            } else {
                if ($response = $this->get("?method=artist.getInfo&autocorrect=1&artist=$name")) {
                    Cache::put($cacheKey, $response->asXML(), 24 * 60 * 7);
                }
            }

            $response = json_decode(json_encode($response), true);

            if (!$response || !$artist = array_get($response, 'artist')) {
                return false;
            }

            return [
                'url' => array_get($artist, 'url'),
                'image' => count($artist['image']) > 3 ? $artist['image'][3] : $artist['image'][0],
                'bio' => [
                    'summary' => $this->formatText(array_get($artist, 'bio.summary')),
                    'full' => $this->formatText(array_get($artist, 'bio.content')),
                ],
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return false;
        }
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

            if ($response = Cache::get($cacheKey)) {
                $response = simplexml_load_string($response);
            } else {
                if ($response = $this->get("?method=album.getInfo&autocorrect=1&album=$name&artist=$artistName")) {
                    Cache::put($cacheKey, $response->asXML(), 24 * 60 * 7);
                }
            }

            $response = json_decode(json_encode($response), true);

            if (!$response || !$album = array_get($response, 'album')) {
                return false;
            }

            return [
                'url' => array_get($album, 'url'),
                'image' => count($album['image']) > 3 ? $album['image'][3] : $album['image'][0],
                'wiki' => [
                    'summary' => $this->formatText(array_get($album, 'wiki.summary')),
                    'full' => $this->formatText(array_get($album, 'wiki.content')),
                ],
                'tracks' => array_map(function ($track) {
                    return [
                        'title' => $track['name'],
                        'length' => (int) $track['duration'],
                        'url' => $track['url'],
                    ];
                }, array_get($album, 'tracks.track', [])),
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return false;
        }
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
}
