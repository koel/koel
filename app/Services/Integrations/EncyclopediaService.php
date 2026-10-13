<?php

namespace App\Services\Integrations;

use App\Exceptions\MusicBrainzBusyException;
use App\Jobs\FetchAlbumInformationJob;
use App\Jobs\FetchArtistInformationJob;
use App\Jobs\FetchEncyclopediaInformationJob;
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
        private readonly CoverArtArchiveService $coverArtArchiveService,
        private readonly WikidataService $wikidataService,
        private readonly MbidService $mbidService,
        private readonly MusicBrainzRateLimiter $rateLimiter,
    ) {}

    public function getAlbumInformation(Album $album): ?AlbumInformation
    {
        try {
            $info = $this->getAlbumInformationOrThrowIfMusicBrainzIsBusy($album);
        } catch (MusicBrainzBusyException) {
            $this->queueInformationFetchIfPossible(new FetchAlbumInformationJob($album));

            $info = AlbumInformation::make();
        }

        return $album->description ? ($info ?? AlbumInformation::make())->withDescription($album->description) : $info;
    }

    /**
     * @throws MusicBrainzBusyException
     */
    public function getAlbumInformationOrThrowIfMusicBrainzIsBusy(Album $album): ?AlbumInformation
    {
        if (!$album->is_identifiable) {
            return null;
        }

        // Identifiers come from MusicBrainz whenever it's enabled, even when another service supplies the entry.
        $this->mbidService->fetchAndStoreAlbumMbids($album);
        $this->mbidService->fetchAndStoreAlbumYear($album);

        return MusicBrainzService::rescueUnlessMusicBrainzIsBusy(
            fn () => Cache::remember(
                self::resolveAlbumInformationCacheKey($album),
                now()->addWeek(),
                fn () => $this->fetchAlbumInformation($album),
            ),
            fn () => $this->fetchAlbumInformation($album),
        );
    }

    public function getArtistInformation(Artist $artist): ?ArtistInformation
    {
        try {
            $info = $this->getArtistInformationOrThrowIfMusicBrainzIsBusy($artist);
        } catch (MusicBrainzBusyException) {
            $this->queueInformationFetchIfPossible(new FetchArtistInformationJob($artist));

            $info = ArtistInformation::make();
        }

        return $artist->description
            ? ($info ?? ArtistInformation::make())->withDescription($artist->description)
            : $info;
    }

    /**
     * @throws MusicBrainzBusyException
     */
    public function getArtistInformationOrThrowIfMusicBrainzIsBusy(Artist $artist): ?ArtistInformation
    {
        if (!$artist->is_standard) {
            return null;
        }

        $this->mbidService->fetchAndStoreArtistMbid($artist);

        return MusicBrainzService::rescueUnlessMusicBrainzIsBusy(
            fn () => Cache::remember(
                self::resolveArtistInformationCacheKey($artist),
                now()->addWeek(),
                fn () => $this->fetchArtistInformation($artist),
            ),
            fn () => $this->fetchArtistInformation($artist),
        );
    }

    public function forgetAlbumInformation(Album $album): void
    {
        Cache::forget(self::resolveAlbumInformationCacheKey($album));
    }

    public function forgetArtistInformation(Artist $artist): void
    {
        Cache::forget(self::resolveArtistInformationCacheKey($artist));
    }

    private static function resolveAlbumInformationCacheKey(Album $album): string
    {
        return cache_key('album information', $album->name, $album->artist->name);
    }

    private static function resolveArtistInformationCacheKey(Artist $artist): string
    {
        return cache_key('artist information', $artist->name);
    }

    private function queueInformationFetchIfPossible(FetchEncyclopediaInformationJob $job): void
    {
        if ($this->rateLimiter->canQueueLookups()) {
            dispatch($job);
        }
    }

    private function fetchAlbumInformation(Album $album): AlbumInformation
    {
        $info = $this->encyclopedia->getAlbumInformation($album) ?: AlbumInformation::make();

        $noCoverSourceAvailable = !CoverArtArchiveService::enabled() && !SpotifyService::enabled() && !$info->cover;

        if ($album->cover || $noCoverSourceAvailable) {
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

        $noImageSourceAvailable = !WikidataService::enabled() && !SpotifyService::enabled() && !$info->image;

        if ($artist->image || $noImageSourceAvailable) {
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
        $coverUrl =
            $this->coverArtArchiveService->tryGetAlbumCover($album)
            ?? (SpotifyService::enabled() ? $this->spotifyService->tryGetAlbumCover($album) : $info->cover);

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
        $imgUrl =
            $this->wikidataService->tryGetArtistImage($artist)
            ?? (SpotifyService::enabled() ? $this->spotifyService->tryGetArtistImage($artist) : $info->image);

        if (!$imgUrl) {
            return null;
        }

        $fileName = $this->imageStorage->storeImage($imgUrl);
        $artist->image = $fileName;
        $artist->save();

        return image_storage_url($fileName);
    }
}
