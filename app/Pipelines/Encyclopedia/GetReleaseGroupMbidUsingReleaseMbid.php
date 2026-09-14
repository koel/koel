<?php

namespace App\Pipelines\Encyclopedia;

use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\Requests\GetReleaseGroupForReleaseRequest;
use Closure;

class GetReleaseGroupMbidUsingReleaseMbid
{
    use TriesRemember;

    public function __construct(
        private readonly MusicBrainzConnector $connector,
    ) {}

    public function __invoke(?string $mbid, Closure $next): mixed
    {
        if (!$mbid) {
            return $next(null);
        }

        $releaseGroupMbid = self::tryRememberForever(
            key: cache_key('release group mbid from release mbid', $mbid),
            nothingFoundTtl: now()->addWeek(),
            callback: fn (): ?string => $this->connector
                ->send(new GetReleaseGroupForReleaseRequest($mbid))
                ->json('release-group.id'),
        );

        return $next($releaseGroupMbid);
    }
}
