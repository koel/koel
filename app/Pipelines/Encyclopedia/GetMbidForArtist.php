<?php

namespace App\Pipelines\Encyclopedia;

use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\Requests\SearchForArtistRequest;
use Closure;
use Illuminate\Support\Facades\Cache;

class GetMbidForArtist
{
    public function __construct(private readonly MusicBrainzConnector $connector)
    {
    }

    public function __invoke(?string $name, Closure $next): mixed
    {
        if (!$name) {
            return $next(null);
        }

        $mbid = Cache::rememberForever(
            cache_key('artist mbid', $name),
            fn () => $this->connector->send(new SearchForArtistRequest($name))->json('artists.0.id'),
        );

        return $next($mbid);
    }
}
