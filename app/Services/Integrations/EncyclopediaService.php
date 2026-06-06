<?php

namespace App\Services\Integrations;

use App\Models\Album;
use App\Models\Artist;
use App\Services\Contracts\Encyclopedia;
use App\Services\Image\ImageStorage;
use App\Values\Album\AlbumInformation;
use App\Values\Artist\ArtistInformation;
use Illuminate\Support\Facades\Cache;

// @mago-ignore lint:cyclomatic-complexity
class EncyclopediaService
{
    public function __construct(
        private readonly Encyclopedia $encyclopedia,
        private readonly ImageStorage $imageStorage,
        private readonly SpotifyService $spotifyService,
    ) {}

    public function getAlbumInformation(Album $album): ?AlbumInformation
    {
        if ($album->is_unknown) {
            return null;
        }

        return rescue(
            fn () => Cache::remember(
                cache_key('album information', $album->name, $album->artist->name),
                now()->addWeek(),
                fn () => $this->fetchAlbumInformation($album),
            ),
            fn () => $this->fetchAlbumInformation($album),
        );
    }

    public function getArtistInformation(Artist $artist): ?ArtistInformation
    {
        if ($artist->is_unknown || $artist->is_various) {
            return null;
        }

        return rescue(
            fn () => Cache::remember(
                cache_key('artist information', $artist->name),
                now()->addWeek(),
                fn () => $this->fetchArtistInformation($artist),
            ),
            fn () => $this->fetchArtistInformation($artist),
        );
    }

    private function fetchAlbumInformation(Album $album): AlbumInformation
    {
        $info = $this->encyclopedia->getAlbumInformation($album) ?: AlbumInformation::make();

        if ($album->cover || !SpotifyService::enabled() && !$info->cover) {
            return $info;
        }

        $info->cover = rescue(
            function () use ($album, $info): ?string {
                return $this->fetchAndStoreAlbumCover($album, $info) ?? $info->cover;
            },
            static fn () => $info->cover,
        );

        return $info;
    }

    private function fetchArtistInformation(Artist $artist): ArtistInformation
    {
        $info = $this->encyclopedia->getArtistInformation($artist) ?: ArtistInformation::make();

        if ($artist->image || !SpotifyService::enabled() && !$info->image) {
            return $info;
        }

        $info->image = rescue(
            function () use ($artist, $info): ?string {
                return $this->fetchAndStoreArtistImage($artist, $info) ?? $info->image;
            },
            static fn () => $info->image,
        );

        return $info;
    }

    private function fetchAndStoreAlbumCover(Album $album, AlbumInformation $info): ?string
    {
        $coverUrl = SpotifyService::enabled() ? $this->spotifyService->tryGetAlbumCover($album) : $info->cover;

        if (!$coverUrl) {
            return null;
        }

        $fileName = $this->imageStorage->storeImage($coverUrl);
        $album->cover = $fileName;
        $album->save();

        return image_storage_url($fileName);
    }

    private function fetchAndStoreArtistImage(Artist $artist, ArtistInformation $info): ?string
    {
        $imgUrl = SpotifyService::enabled() ? $this->spotifyService->tryGetArtistImage($artist) : $info->image;

        if (!$imgUrl) {
            return null;
        }

        $fileName = $this->imageStorage->storeImage($imgUrl);
        $artist->image = $fileName;
        $artist->save();

        return image_storage_url($fileName);
    }
}
