<?php

namespace App\Services\Integrations;

use App\Exceptions\MusicBrainzBusyException;
use App\Jobs\FetchAlbumInformationJob;
use App\Jobs\FetchArtistInformationJob;
use App\Models\Album;
use App\Models\Artist;
use App\Services\Contracts\Encyclopedia;
use App\Services\Image\ImageStorage;
use App\Values\Album\AlbumInformation;
use App\Values\Artist\ArtistInformation;
use Illuminate\Support\Facades\Cache;
use Throwable;

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
            return $this->getAlbumInformationOrThrowIfMusicBrainzIsBusy($album);
        } catch (MusicBrainzBusyException) {
            $this->queueInformationFetchIfPossible(new FetchAlbumInformationJob($album));

            return AlbumInformation::make();
        }
    }

    /**
     * @throws MusicBrainzBusyException
     */
    public function getAlbumInformationOrThrowIfMusicBrainzIsBusy(Album $album): ?AlbumInformation
    {
        if ($album->is_unknown) {
            return null;
        }

        // Identifiers come from MusicBrainz whenever it's enabled, even when another service supplies the entry.
        $this->mbidService->fetchAndStoreAlbumMbids($album);
        $this->mbidService->fetchAndStoreAlbumYear($album);

        return rescue(
            fn () => Cache::remember(
                cache_key('album information', $album->name, $album->artist->name),
                now()->addWeek(),
                fn () => $this->fetchAlbumInformation($album),
            ),
            fn (Throwable $e) => $e instanceof MusicBrainzBusyException
                ? throw $e
                : $this->fetchAlbumInformation($album),
            report: static fn (Throwable $e): bool => !$e instanceof MusicBrainzBusyException,
        );
    }

    public function getArtistInformation(Artist $artist): ?ArtistInformation
    {
        try {
            return $this->getArtistInformationOrThrowIfMusicBrainzIsBusy($artist);
        } catch (MusicBrainzBusyException) {
            $this->queueInformationFetchIfPossible(new FetchArtistInformationJob($artist));

            return ArtistInformation::make();
        }
    }

    /**
     * @throws MusicBrainzBusyException
     */
    public function getArtistInformationOrThrowIfMusicBrainzIsBusy(Artist $artist): ?ArtistInformation
    {
        if ($artist->is_unknown || $artist->is_various) {
            return null;
        }

        $this->mbidService->fetchAndStoreArtistMbid($artist);

        return rescue(
            fn () => Cache::remember(
                cache_key('artist information', $artist->name),
                now()->addWeek(),
                fn () => $this->fetchArtistInformation($artist),
            ),
            fn (Throwable $e) => $e instanceof MusicBrainzBusyException
                ? throw $e
                : $this->fetchArtistInformation($artist),
            report: static fn (Throwable $e): bool => !$e instanceof MusicBrainzBusyException,
        );
    }

    private function queueInformationFetchIfPossible(FetchAlbumInformationJob|FetchArtistInformationJob $job): void
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
