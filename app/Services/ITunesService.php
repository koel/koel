<?php

namespace App\Services;

use App\Http\Integrations\iTunes\ITunesConnector;
use App\Http\Integrations\iTunes\Requests\GetTrackRequest;
use App\Models\Album;
use Illuminate\Cache\Repository as Cache;

class ITunesService
{
    public function __construct(private ITunesConnector $connector, private Cache $cache)
    {
    }

    public static function used(): bool
    {
        return (bool) config('koel.itunes.enabled');
    }

    public function getTrackUrl(string $trackName, Album $album): ?string
    {
        return attempt(function () use ($trackName, $album): ?string {
            $request = new GetTrackRequest($trackName, $album);
            $hash = md5(serialize($request->query()));

            return $this->cache->remember(
                "itunes.track.$hash",
                now()->addWeek(),
                function () use ($request): ?string {
                    $response = $this->connector->send($request)->object();

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
