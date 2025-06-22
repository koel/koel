<?php

namespace App\Services;

use App\Http\Integrations\iTunes\ITunesConnector;
use App\Http\Integrations\iTunes\Requests\GetTrackRequest;
use App\Models\Album;
use Illuminate\Support\Facades\Cache;

class ITunesService
{
    public function __construct(private readonly ITunesConnector $connector)
    {
    }

    public static function used(): bool
    {
        return (bool) config('koel.services.itunes.enabled');
    }

    public function getTrackUrl(string $trackName, Album $album): ?string
    {
        return rescue(function () use ($trackName, $album): ?string {
            $request = new GetTrackRequest($trackName, $album);

            return Cache::remember(
                cache_key('iTunes track URL', serialize($request->query())),
                now()->addWeek(),
                function () use ($request): ?string {
                    $response = $this->connector->send($request)->object();

                    if (!$response->resultCount) {
                        return null;
                    }

                    $trackUrl = $response->results[0]->trackViewUrl;
                    $connector = parse_url($trackUrl, PHP_URL_QUERY) ? '&' : '?';

                    return $trackUrl . "{$connector}at=" . config('koel.services.itunes.affiliate_id');
                }
            );
        });
    }
}
