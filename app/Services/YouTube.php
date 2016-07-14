<?php

namespace App\Services;

use Cache;
use GuzzleHttp\Client;

class YouTube extends RESTfulService
{
    /**
     * Construct an instance of YouTube service.
     *
     * @param string      $key    The YouTube API key
     * @param Client|null $client The Guzzle HTTP client
     */
    public function __construct($key = null, Client $client = null)
    {
        parent::__construct(
            $key ?: env('YOUTUBE_API_KEY'),
            null,
            'https://www.googleapis.com/youtube/v3',
            $client ?: new Client()
        );
    }

    /**
     * Determine if our application is using YouTube.
     *
     * @return bool
     */
    public function enabled()
    {
        return (bool) env('YOUTUBE_API_KEY');
    }

    /**
     * Search for YouTube videos.
     *
     * @param string $q         The query string
     * @param string $pageToken YouTube page token (e.g. for next/previous page)
     * @param int    $perPage   Number of results per page
     *
     * @return object|false
     */
    public function search($q, $pageToken = '', $perPage = 10)
    {
        if (!$this->enabled()) {
            return false;
        }

        $uri = sprintf('search?part=snippet&maxResults=%s&pageToken=%s&q=%s',
            $perPage,
            urlencode($pageToken),
            urlencode($q)
        );

        $cacheKey = md5("youtube_$uri");
        if ($response = Cache::get($cacheKey)) {
            return $response;
        }

        if ($response = $this->get($uri)) {
            // Cache the result for 7 days
            Cache::put($cacheKey, $response, 60 * 24 * 7);
        }

        return $response;
    }
}
