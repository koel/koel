<?php

namespace App\Services;

use App\Http\Integrations\YouTube\Requests\SearchVideosRequest;
use App\Http\Integrations\YouTube\YouTubeConnector;
use App\Models\Song;
use Illuminate\Cache\Repository as Cache;
use Throwable;

class YouTubeService
{
    public function __construct(private readonly YouTubeConnector $connector, private readonly Cache $cache)
    {
    }

    public static function enabled(): bool
    {
        return (bool) config('koel.youtube.key');
    }

    public function searchVideosRelatedToSong(Song $song, string $pageToken = ''): ?object
    {
        if (!self::enabled()) {
            return null;
        }

        $request = new SearchVideosRequest($song, $pageToken);
        $hash = md5(serialize($request->query()->all()));

        try {
            return $this->cache->remember(
                "youtube.$hash",
                now()->addWeek(),
                fn () => $this->connector->send($request)->object()
            );
        } catch (Throwable) {
            return null;
        }
    }
}
