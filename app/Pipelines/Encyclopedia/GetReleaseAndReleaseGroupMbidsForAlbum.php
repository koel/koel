<?php

namespace App\Pipelines\Encyclopedia;

use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\Requests\SearchForReleaseRequest;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class GetReleaseAndReleaseGroupMbidsForAlbum
{
    public function __construct(private readonly MusicBrainzConnector $connector)
    {
    }

    public function __invoke(?array $params, Closure $next): mixed
    {
        if (!$params) {
            return $next(null);
        }

        [$releaseMbid, $releaseGroupMbid] = Cache::rememberForever(
            cache_key('release and release group mbids', $params['album'], $params['artist']),
            function () use ($params) {
                $response = $this->connector->send(new SearchForReleaseRequest($params['album'], $params['artist']));

                // Opportunistically, cache the artist mbids as well.
                // Our future requests for artist mbids will be faster this way.
                foreach ($response->json('releases.0.artist-credit', []) as $credit) {
                    Cache::forever(
                        cache_key('artist mbid', Arr::get($credit, 'artist.name')),
                        Arr::get($credit, 'artist.id'),
                    );
                }

                return [
                    $response->json('releases.0.id'),
                    $response->json('releases.0.release-group.id'),
                ];
            }
        );

        return $next([$releaseMbid, $releaseGroupMbid]);
    }
}
