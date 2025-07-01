<?php

namespace App\Pipelines\Encyclopedia;

use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\Requests\GetRecordingsRequest;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class GetAlbumTracksUsingMbid
{
    public function __construct(private readonly MusicBrainzConnector $connector)
    {
    }

    public function __invoke(?string $mbid, Closure $next): mixed
    {
        if (!$mbid) {
            return $next(null);
        }

        $tracks = Cache::rememberForever(
            cache_key('album tracks', $mbid),
            function () use ($mbid): array {
                $tracks = [];

                // There can be multiple media entries (e.g. CDs) in a release, each with its own set of tracks.
                // To simplify things, we will collect all tracks from all media entries.
                foreach ($this->connector->send(new GetRecordingsRequest($mbid))->json('media', []) as $media) {
                    array_push($tracks, ...Arr::get($media, 'tracks', []));
                }

                return $tracks;
            },
        );

        return $next($tracks);
    }
}
