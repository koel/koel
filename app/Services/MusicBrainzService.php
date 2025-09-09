<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Pipelines\Encyclopedia\GetAlbumTracksUsingMbid;
use App\Pipelines\Encyclopedia\GetAlbumWikidataIdUsingReleaseGroupMbid;
use App\Pipelines\Encyclopedia\GetArtistWikidataIdUsingMbid;
use App\Pipelines\Encyclopedia\GetMbidForArtist;
use App\Pipelines\Encyclopedia\GetReleaseAndReleaseGroupMbidsForAlbum;
use App\Pipelines\Encyclopedia\GetWikipediaPageSummaryUsingPageTitle;
use App\Pipelines\Encyclopedia\GetWikipediaPageTitleUsingWikidataId;
use App\Services\Contracts\Encyclopedia;
use App\Values\Album\AlbumInformation;
use App\Values\Artist\ArtistInformation;
use Illuminate\Support\Facades\Pipeline;

class MusicBrainzService implements Encyclopedia
{
    public static function enabled(): bool
    {
        return config('koel.services.musicbrainz.enabled');
    }

    public function getArtistInformation(Artist $artist): ?ArtistInformation
    {
        if ($artist->is_unknown || $artist->is_various) {
            return null;
        }

        return rescue_if(static::enabled(), static function () use ($artist) {
            $wikipediaSummary = Pipeline::send($artist->name)
                ->through([
                    GetMbidForArtist::class,
                    GetArtistWikidataIdUsingMbid::class,
                    GetWikipediaPageTitleUsingWikidataId::class,
                    GetWikipediaPageSummaryUsingPageTitle::class,
                ])
                ->thenReturn();

            return $wikipediaSummary
                ? ArtistInformation::fromWikipediaSummary($wikipediaSummary)
                : null;
        });
    }

    public function getAlbumInformation(Album $album): ?AlbumInformation
    {
        if ($album->is_unknown || $album->artist->is_unknown) {
            return null;
        }

        return rescue_if(static::enabled(), static function () use ($album): ?AlbumInformation {
            // MusicBrainz has the concept of a "release" and a "release group".
            // A release is a specific version of an album, which contains the actual tracks.
            // A release group is a collection of releases (e.g. different formats or editions or markets
            // of the same album), which contains metadata like the Wikidata relationship.
            /**
             * @var string|null $albumMbid
             * @var string|null $releaseGroupMbid
             */
            [$albumMbid, $releaseGroupMbid] = Pipeline::send([
                'album' => $album->name,
                'artist' => $album->artist->name,
            ])
                ->through([GetReleaseAndReleaseGroupMbidsForAlbum::class])
                ->thenReturn();

            if (!$albumMbid || !$releaseGroupMbid) {
                return null;
            }

            /** @var array<mixed> $tracks */
            $tracks = Pipeline::send($albumMbid)
                ->through([GetAlbumTracksUsingMbid::class])
                ->thenReturn() ?: [];

            $wikipediaSummary = Pipeline::send($releaseGroupMbid)
                ->through([
                    GetAlbumWikidataIdUsingReleaseGroupMbid::class,
                    GetWikipediaPageTitleUsingWikidataId::class,
                    GetWikipediaPageSummaryUsingPageTitle::class,
                ])
                ->thenReturn();

            return $wikipediaSummary
                ? AlbumInformation::fromWikipediaSummary($wikipediaSummary)->withMusicBrainzTracks($tracks)
                : AlbumInformation::make(url: "https://musicbrainz.org/release/$albumMbid")
                    ->withMusicBrainzTracks($tracks);
        });
    }
}
