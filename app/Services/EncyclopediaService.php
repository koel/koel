<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Services\Contracts\Encyclopedia;
use App\Values\Album\AlbumInformation;
use App\Values\Artist\ArtistInformation;
use Illuminate\Support\Facades\Cache;

class EncyclopediaService
{
    public function __construct(
        private readonly Encyclopedia $encyclopedia,
        private readonly ImageStorage $imageStorage,
        private readonly SpotifyService $spotifyService,
    ) {
    }

    public function getAlbumInformation(Album $album): ?AlbumInformation
    {
        if ($album->is_unknown) {
            return null;
        }

        return Cache::remember(
            cache_key('album information', $album->name, $album->artist->name),
            now()->addWeek(),
            function () use ($album): AlbumInformation {
                $info = $this->encyclopedia->getAlbumInformation($album) ?: AlbumInformation::make();

                if ($album->has_cover || (!SpotifyService::enabled() && !$info->cover)) {
                    // If the album already has a cover, or there's no resource to download a cover from,
                    // just return the info.
                    return $info;
                }

                // If the album cover is not set, try to download it either from Spotify (prioritized, due to
                // the high quality) or from the encyclopedia. We will also set the downloaded image right
                // away into the info object so that the caller/client can use it immediately.
                $info->cover = rescue(function () use ($album, $info): ?string {
                    return $this->fetchAndStoreAlbumCover($album, $info) ?? $info->cover;
                });

                return $info;
            }
        );
    }

    public function getArtistInformation(Artist $artist): ?ArtistInformation
    {
        if ($artist->is_unknown || $artist->is_various) {
            return null;
        }

        return Cache::remember(
            cache_key('artist information', $artist->name),
            now()->addWeek(),
            function () use ($artist): ArtistInformation {
                $info = $this->encyclopedia->getArtistInformation($artist) ?: ArtistInformation::make();

                if ($artist->has_image || (!SpotifyService::enabled() && !$info->image)) {
                    // If the artist already has an image, or there's no resource to download an image from,
                    // just return the info.
                    return $info;
                }

                // If the artist image is not set, try to download it either from Spotify (prioritized, due to
                // the high quality) or from the encyclopedia. We will also set the downloaded image right
                // away into the info object so that the caller/client can use it immediately.
                $info->image = rescue(function () use ($artist, $info): ?string {
                    return $this->fetchAndStoreArtistImage($artist, $info) ?? $info->image;
                });

                return $info;
            }
        );
    }

    private function fetchAndStoreAlbumCover(Album $album, AlbumInformation $info): ?string
    {
        $coverUrl = SpotifyService::enabled()
            ? $this->spotifyService->tryGetAlbumCover($album)
            : $info->cover;

        return $coverUrl ? $this->imageStorage->storeAlbumCover($album, $coverUrl) : null;
    }

    private function fetchAndStoreArtistImage(Artist $artist, ArtistInformation $info): ?string
    {
        $imgUrl = SpotifyService::enabled()
            ? $this->spotifyService->tryGetArtistImage($artist)
            : $info->image;

        return $imgUrl ? $this->imageStorage->storeArtistImage($artist, $imgUrl) : null;
    }
}
