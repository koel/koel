<?php

namespace App\Services;

use App\Models\Song;

class YouTubeService extends AbstractApiClient implements ApiConsumerInterface
{
    /**
     * Determine if our application is using YouTube.
     */
    public function enabled(): bool
    {
        return (bool) $this->getKey();
    }

    /**
     * Search for YouTube videos related to a song.
     *
     * @return mixed|null
     */
    public function searchVideosRelatedToSong(Song $song, string $pageToken = '')
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
     * @param string $q         The query string
     * @param string $pageToken YouTube page token (e.g. for next/previous page)
     * @param int    $perPage   Number of results per page
     *
     * @return mixed|null
     */
    public function search(string $q, string $pageToken = '', int $perPage = 10)
    {
        if (!$this->enabled()) {
            return;
        }

        $uri = sprintf('search?part=snippet&type=video&maxResults=%s&pageToken=%s&q=%s',
            $perPage,
            urlencode($pageToken),
            urlencode($q)
        );

        return $this->cache->remember(md5("youtube_$uri"), 60 * 24 * 7, function () use ($uri) {
            return $this->get($uri);
        });
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
