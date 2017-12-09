<?php

namespace App\Services;

use Cache;
use Exception;
use GuzzleHttp\Client;
use Log;

class iTunes
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $endPoint = 'https://itunes.apple.com/search';

    /**
     * iTunes constructor.
     *
     * @param Client|null $client
     */
    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    /**
     * Determines whether to use iTunes services.
     *
     * @return bool
     */
    public function used()
    {
        return (bool) config('koel.itunes.enabled');
    }

    /**
     * Search for a track on iTunes Store with the given information and get its URL.
     *
     * @param $term string The main query string (should be the track's name)
     * @param $album string The album's name, if available
     * @param $artist string The artist's name, if available
     *
     * @return string|false
     */
    public function getTrackUrl($term, $album = '', $artist = '')
    {
        try {
            return Cache::remember(md5("itunes_track_url_{$term}{$album}{$artist}"), 24 * 60 * 7,
                function () use ($term, $album, $artist) {
                    $params = [
                        'term' => $term.($album ? " $album" : '').($artist ? " $artist" : ''),
                        'media' => 'music',
                        'entity' => 'song',
                        'limit' => 1,
                    ];

                    $response = (string) $this->client->get($this->endPoint, ['query' => $params])->getBody();
                    $response = json_decode($response);

                    if (!$response->resultCount) {
                        return false;
                    }

                    $trackUrl = $response->results[0]->trackViewUrl;
                    $connector = parse_url($trackUrl, PHP_URL_QUERY) ? '&' : '?';
                    $trackUrl .= "{$connector}at=".config('koel.itunes.affiliate_id');

                    return $trackUrl;
                }
            );
        } catch (Exception $e) {
            Log::error($e);

            return false;
        }
    }
}
