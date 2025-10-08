<?php

namespace App\Pipelines\Encyclopedia;

use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\Requests\GetRecordingsRequest;
use Closure;
use Illuminate\Support\Arr;

class GetAlbumTracksUsingMbid
{
    use TriesRemember;

    public function __construct(private readonly MusicBrainzConnector $connector)
    {
    }

    public function __invoke(?string $mbid, Closure $next): mixed
    {
        if (!$mbid) {
            return $next(null);
        }

        $tracks = $this->tryRememberForever(
            key: cache_key('album tracks', $mbid),
            callback: function () use ($mbid): array {
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
