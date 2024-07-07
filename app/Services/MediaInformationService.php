<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Services\Contracts\MusicEncyclopedia;
use App\Values\AlbumInformation;
use App\Values\ArtistInformation;
use Illuminate\Support\Facades\Cache;

class MediaInformationService
{
    public function __construct(
        private readonly MusicEncyclopedia $encyclopedia,
        private readonly MediaMetadataService $mediaMetadataService
    ) {
    }

    public function getAlbumInformation(Album $album): ?AlbumInformation
    {
        if ($album->is_unknown) {
            return null;
        }

        return Cache::remember("album.info.$album->id", now()->addWeek(), function () use ($album): AlbumInformation {
            $info = $this->encyclopedia->getAlbumInformation($album) ?: AlbumInformation::make();

            attempt_unless($album->has_cover, function () use ($info, $album): void {
                $this->mediaMetadataService->tryDownloadAlbumCover($album);
                $info->cover = $album->cover;
            });

            return $info;
        });
    }

    public function getArtistInformation(Artist $artist): ?ArtistInformation
    {
        if ($artist->is_unknown || $artist->is_various) {
            return null;
        }

        return Cache::remember(
            "artist.info.$artist->id",
            now()->addWeek(),
            function () use ($artist): ArtistInformation {
                $info = $this->encyclopedia->getArtistInformation($artist) ?: ArtistInformation::make();

                attempt_unless($artist->has_image, function () use ($artist, $info): void {
                    $this->mediaMetadataService->tryDownloadArtistImage($artist);
                    $info->image = $artist->image;
                });

                return $info;
            }
        );
    }
}
