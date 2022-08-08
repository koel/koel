<?php

namespace App\Services;

use App\Models\Song;
use App\Services\ApiClients\YouTubeClient;
use Illuminate\Cache\Repository as Cache;

class YouTubeService
{
    public function __construct(private YouTubeClient $client, private Cache $cache)
    {
    }

    /**
     * Determine if our application is using YouTube.
     */
    public static function enabled(): bool
    {
        return (bool) config('koel.youtube.key');
    }

    public function searchVideosRelatedToSong(Song $song, string $pageToken = '') // @phpcs:ignore
    {
        $q = $song->title;

        // If the artist is worth noticing, include them into the search.
        if (!$song->artist->is_unknown && !$song->artist->is_various) {
            $q .= " {$song->artist->name}";
        }

        return $this->search($q, $pageToken);
    }

    /**
     * Search for YouTube videos by a query string.
     *
     * @param string $q The query string
     * @param string $pageToken YouTube page token (e.g. for next/previous page)
     * @param int $perPage Number of results per page
     *
     */
    private function search(string $q, string $pageToken = '', int $perPage = 10) // @phpcs:ignore
    {
        return attempt_if(static::enabled(), function () use ($q, $pageToken, $perPage) {
            $uri = sprintf(
                'search?part=snippet&type=video&maxResults=%s&pageToken=%s&q=%s',
                $perPage,
                urlencode($pageToken),
                urlencode($q)
            );

            return $this->cache->remember(md5("youtube_$uri"), now()->addWeek(), fn () => $this->client->get($uri));
        });
    }
}
