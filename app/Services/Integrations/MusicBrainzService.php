<?php

namespace App\Services\Integrations;

use App\Exceptions\MusicBrainzBusyException;
use App\Models\Album;
use App\Models\Artist;
use App\Pipelines\Encyclopedia\GetAlbumTracksUsingMbid;
use App\Pipelines\Encyclopedia\GetAlbumWikidataIdUsingReleaseGroupMbid;
use App\Pipelines\Encyclopedia\GetArtistWikidataIdUsingMbid;
use App\Pipelines\Encyclopedia\GetMbidForArtist;
use App\Pipelines\Encyclopedia\GetReleaseAndReleaseGroupMbidsForAlbum;
use App\Pipelines\Encyclopedia\GetReleaseGroupMbidUsingReleaseMbid;
use App\Pipelines\Encyclopedia\GetWikipediaPageSummaryUsingPageTitle;
use App\Pipelines\Encyclopedia\GetWikipediaPageTitleUsingWikidataId;
use App\Services\Contracts\Encyclopedia;
use App\Values\Album\AlbumInformation;
use App\Values\Artist\ArtistInformation;
use Closure;
use Illuminate\Support\Facades\Pipeline;
use Throwable;

class MusicBrainzService implements Encyclopedia
{
    public static function enabled(): bool
    {
        return config('koel.services.musicbrainz.enabled');
    }

    public static function userAgent(): string
    {
        return config('koel.services.musicbrainz.user_agent') ?: koel_user_agent();
    }

    public function getArtistInformation(Artist $artist): ?ArtistInformation
    {
        if ($artist->is_unknown || $artist->is_various) {
            return null;
        }

        if (!static::enabled()) {
            return null;
        }

        return self::rescueUnlessMusicBrainzIsBusy(static function () use ($artist): ?ArtistInformation {
            /** @var string|null $mbid */
            $mbid = $artist->mbid ?: Pipeline::send($artist->name)->through([GetMbidForArtist::class])->thenReturn();

            $wikipediaSummary = Pipeline::send($mbid)
                ->through([
                    GetArtistWikidataIdUsingMbid::class,
                    GetWikipediaPageTitleUsingWikidataId::class,
                    GetWikipediaPageSummaryUsingPageTitle::class,
                ])
                ->thenReturn();

            return $wikipediaSummary ? ArtistInformation::fromWikipediaSummary($wikipediaSummary) : null;
        });
    }

    /** @return array{0: ?string, 1: ?string} The release and release group identifiers */
    private static function resolveReleaseMbids(Album $album): array
    {
        if ($album->mbid) {
            /** @var string|null $releaseGroupMbid */
            $releaseGroupMbid = Pipeline::send($album->mbid)
                ->through([GetReleaseGroupMbidUsingReleaseMbid::class])
                ->thenReturn();

            return [$album->mbid, $releaseGroupMbid];
        }

        return Pipeline::send([
            'album' => $album->name,
            'artist' => $album->artist->name,
        ])->through([GetReleaseAndReleaseGroupMbidsForAlbum::class])->thenReturn();
    }

    public function getAlbumInformation(Album $album): ?AlbumInformation
    {
        if ($album->is_unknown || $album->artist->is_unknown) {
            return null;
        }

        if (!static::enabled()) {
            return null;
        }

        return self::rescueUnlessMusicBrainzIsBusy(static function () use ($album): ?AlbumInformation {
            // MusicBrainz has the concept of a "release" and a "release group".
            // A release is a specific version of an album, which contains the actual tracks.
            // A release group is a collection of releases (e.g. different formats or editions or markets
            // of the same album), which contains metadata like the Wikidata relationship.
            [$albumMbid, $releaseGroupMbid] = self::resolveReleaseMbids($album);

            if (!$albumMbid || !$releaseGroupMbid) {
                return null;
            }

            /** @var array<mixed> $tracks */
            $tracks = Pipeline::send($albumMbid)->through([GetAlbumTracksUsingMbid::class])->thenReturn() ?: [];

            $wikipediaSummary = Pipeline::send($releaseGroupMbid)
                ->through([
                    GetAlbumWikidataIdUsingReleaseGroupMbid::class,
                    GetWikipediaPageTitleUsingWikidataId::class,
                    GetWikipediaPageSummaryUsingPageTitle::class,
                ])
                ->thenReturn();

            return $wikipediaSummary
                ? AlbumInformation::fromWikipediaSummary($wikipediaSummary)->withMusicBrainzTracks($tracks)
                : AlbumInformation::make(url: "https://musicbrainz.org/release/$albumMbid")->withMusicBrainzTracks(
                    $tracks,
                );
        });
    }

    /**
     * A lookup refused for lack of a free request slot is not a failure: the caller retries it later.
     *
     * @template TResult
     *
     * @param Closure(): TResult $callback
     *
     * @return TResult|null
     */
    private static function rescueUnlessMusicBrainzIsBusy(Closure $callback): mixed
    {
        return rescue(
            $callback,
            static fn (Throwable $e) => $e instanceof MusicBrainzBusyException ? throw $e : null,
            report: static fn (Throwable $e): bool => !$e instanceof MusicBrainzBusyException,
        );
    }
}
