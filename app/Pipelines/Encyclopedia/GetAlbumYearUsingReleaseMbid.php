<?php

namespace App\Pipelines\Encyclopedia;

use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\Requests\GetReleaseGroupForReleaseRequest;
use Closure;

class GetAlbumYearUsingReleaseMbid
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

        $year = self::tryRememberForever(
            key: cache_key('album year from release mbid', $mbid),
            nothingFoundTtl: now()->addWeek(),
            callback: fn (): ?int => self::parseYear(
                $this->connector
                    ->send(new GetReleaseGroupForReleaseRequest($mbid))
                    ->json('release-group.first-release-date'),
            ),
        );

        return $next($year);
    }

    private static function parseYear(?string $firstReleaseDate): ?int
    {
        return preg_match('/^(\d{4})(-\d{2}){0,2}$/', (string) $firstReleaseDate, $matches) ? (int) $matches[1] : null;
    }
}
