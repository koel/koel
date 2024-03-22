<?php

namespace App\Services;

use App\Http\Integrations\YouTube\Requests\SearchVideosRequest;
use App\Http\Integrations\YouTube\YouTubeConnector;
use App\Models\Song;
use Illuminate\Cache\Repository as Cache;

class YouTubeService
{
    public function __construct(private YouTubeConnector $connector, private Cache $cache)
    {
    }

    public static function enabled(): bool
    {
        return (bool) config('koel.youtube.key');
    }

    /** @return array<mixed>|null */
    public function searchVideosRelatedToSong(Song $song, string $pageToken = ''): ?array
    {
        if (!self::enabled()) {
            return null;
        }

        $request = new SearchVideosRequest($song, $pageToken);
        $hash = md5(serialize($request->query()->all()));

        return $this->cache->remember(
            "youtube:$hash",
            now()->addWeek(),
            fn () => $this->connector->send($request)->json()
        );
    }
}
