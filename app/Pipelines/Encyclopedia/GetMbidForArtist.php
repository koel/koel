<?php

namespace App\Pipelines\Encyclopedia;

use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\Requests\SearchForArtistRequest;
use Closure;

class GetMbidForArtist
{
    use TriesRemember;

    public function __construct(private readonly MusicBrainzConnector $connector)
    {
    }

    public function __invoke(?string $name, Closure $next): mixed
    {
        if (!$name) {
            return $next(null);
        }

        $mbid = $this->tryRememberForever(
            key: cache_key('artist mbid', $name),
            callback: fn () => $this->connector->send(new SearchForArtistRequest($name))->json('artists.0.id'),
        );

        return $next($mbid);
    }
}
