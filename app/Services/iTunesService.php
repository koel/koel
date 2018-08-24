<?php

namespace App\Services;

use Cache;
use Exception;
use Log;

class iTunesService extends ApiClient implements ApiConsumerInterface
{
    /**
     * Determines whether to use iTunes services.
     */
    public function used(): bool
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
    public function getTrackUrl(string $term, string $album = '', string $artist = ''): ?string
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

                    $response = json_decode(
                        $this->getClient()->get($this->getEndpoint(), ['query' => $params])->getBody()
                    );

                    if (!$response->resultCount) {
                        return null;
                    }

                    $trackUrl = $response->results[0]->trackViewUrl;
                    $connector = parse_url($trackUrl, PHP_URL_QUERY) ? '&' : '?';
                    $trackUrl .= "{$connector}at=".config('koel.itunes.affiliate_id');

                    return $trackUrl;
                }
            );
        } catch (Exception $e) {
            Log::error($e);

            return null;
        }
    }

    public function getKey(): ?string
    {
        return null;
    }

    public function getSecret(): ?string
    {
        return null;
    }

    public function getEndpoint(): string
    {
        return config('koel.itunes.endpoint');
    }
}
