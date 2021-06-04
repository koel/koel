<?php

namespace App\Services;

use App\Models\Song;
use Throwable;

class YouTubeService extends AbstractApiClient implements ApiConsumerInterface
{
    /**
     * Determine if our application is using YouTube.
     */
    public function enabled(): bool
    {
        return (bool) $this->getKey();
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
    public function search(string $q, string $pageToken = '', int $perPage = 10) // @phpcs:ignore
    {
        if (!$this->enabled()) {
            return null;
        }

        $uri = sprintf(
            'search?part=snippet&type=video&maxResults=%s&pageToken=%s&q=%s',
            $perPage,
            urlencode($pageToken),
            urlencode($q)
        );

        try {
            return $this->cache->remember(md5("youtube_$uri"), 60 * 24 * 7, fn () => $this->get($uri));
        } catch (Throwable $e) {
            $this->logger->error($e);

            return null;
        }
    }

    public function getEndpoint(): ?string
    {
        return config('koel.youtube.endpoint');
    }

    public function getKey(): ?string
    {
        return config('koel.youtube.key');
    }

    public function getSecret(): ?string
    {
        return null;
    }
}
