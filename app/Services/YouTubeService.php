<?php

namespace App\Services;

use App\Models\Song;
use Cache;

class YouTubeService extends ApiClient implements ApiConsumerInterface
{
    /**
     * Determine if our application is using YouTube.
     *
     * @return bool
     */
    public function enabled()
    {
        return (bool) $this->getKey();
    }

    /**
     * Search for YouTube videos related to a song.
     *
     * @param Song   $song
     * @param string $pageToken
     *
     * @return object|false
     */
    public function searchVideosRelatedToSong(Song $song, $pageToken = '')
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
     * @return object|false
     */
    public function search($q, $pageToken = '', $perPage = 10)
    {
        if (!$this->enabled()) {
            return false;
        }

        $uri = sprintf('search?part=snippet&type=video&maxResults=%s&pageToken=%s&q=%s',
            $perPage,
            urlencode($pageToken),
            urlencode($q)
        );

        return Cache::remember(md5("youtube_$uri"), 60 * 24 * 7, function () use ($uri) {
            return $this->get($uri);
        });
    }

    /** @return string */
    public function getEndpoint()
    {
        return config('koel.youtube.endpoint');
    }

    /** @return string */
    public function getKey()
    {
        return config('koel.youtube.key');
    }

    /** @return string|null */
    public function getSecret()
    {
    }
}
