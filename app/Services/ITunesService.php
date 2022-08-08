<?php

namespace App\Services;

use App\Services\ApiClients\ITunesClient;
use Illuminate\Cache\Repository as Cache;

class ITunesService
{
    public function __construct(private ITunesClient $client, private Cache $cache)
    {
    }

    /**
     * Determines whether to use iTunes services.
     */
    public static function used(): bool
    {
        return (bool) config('koel.itunes.enabled');
    }

    /**
     * Search for a track on iTunes Store with the given information and get its URL.
     *
     * @param string $term The main query string (should be the track's name)
     * @param string $album The album's name, if available
     * @param string $artist The artist's name, if available
     */
    public function getTrackUrl(string $term, string $album = '', string $artist = ''): ?string
    {
        return attempt(function () use ($term, $album, $artist): ?string {
            return $this->cache->remember(
                md5("itunes_track_url_$term$album$artist"),
                24 * 60 * 7,
                function () use ($term, $album, $artist): ?string {
                    $params = [
                        'term' => $term . ($album ? " $album" : '') . ($artist ? " $artist" : ''),
                        'media' => 'music',
                        'entity' => 'song',
                        'limit' => 1,
                    ];

                    $response = $this->client->get('/', ['query' => $params]);

                    if (!$response->resultCount) {
                        return null;
                    }

                    $trackUrl = $response->results[0]->trackViewUrl;
                    $connector = parse_url($trackUrl, PHP_URL_QUERY) ? '&' : '?';

                    return $trackUrl . "{$connector}at=" . config('koel.itunes.affiliate_id');
                }
            );
        });
    }
}
