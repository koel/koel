<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Values\AlbumInformation;
use App\Values\ArtistInformation;
use Illuminate\Cache\Repository as Cache;

class MediaInformationService
{
    public function __construct(
        private MusicEncyclopedia $encyclopedia,
        private MediaMetadataService $mediaMetadataService,
        private Cache $cache
    ) {
    }

    public function getAlbumInformation(Album $album): ?AlbumInformation
    {
        if ($album->is_unknown) {
            return null;
        }

        if ($this->cache->has('album.info.' . $album->id)) {
            return $this->cache->get('album.info.' . $album->id);
        }

        $info = $this->encyclopedia->getAlbumInformation($album) ?: AlbumInformation::make();

        attempt_unless($album->has_cover, function () use ($info, $album): void {
            $this->mediaMetadataService->tryDownloadAlbumCover($album);
            $info->cover = $album->cover;
        });

        $this->cache->put('album.info.' . $album->id, $info, now()->addWeek());

        return $info;
    }

    public function getArtistInformation(Artist $artist): ?ArtistInformation
    {
        if ($artist->is_unknown || $artist->is_various) {
            return null;
        }

        if ($this->cache->has('artist.info.' . $artist->id)) {
            return $this->cache->get('artist.info.' . $artist->id);
        }

        $info = $this->encyclopedia->getArtistInformation($artist) ?: ArtistInformation::make();

        attempt_unless($artist->has_image, function () use ($artist, $info): void {
            $this->mediaMetadataService->tryDownloadArtistImage($artist);
            $info->image = $artist->image;
        });

        $this->cache->put('artist.info.' . $artist->id, $info, now()->addWeek());

        return $info;
    }
}
