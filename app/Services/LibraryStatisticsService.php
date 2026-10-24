<?php

namespace App\Services;

use App\Enums\LibraryStatisticUnit;
use App\Hooks\Filter;
use App\Models\User;
use App\Repositories\PodcastRepository;
use App\Repositories\RadioStationRepository;
use App\Repositories\SongRepository;
use App\Values\Statistics\LibraryStatistic;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Collection;

class LibraryStatisticsService
{
    public function __construct(
        private readonly LibrarySummaryService $librarySummaryService,
        private readonly SongRepository $songRepository,
        private readonly PodcastRepository $podcastRepository,
        private readonly RadioStationRepository $radioStationRepository,
        #[Config('koel.podcasts.enabled')]
        private readonly bool $podcastsEnabled = true,
        #[Config('koel.radio.enabled')]
        private readonly bool $radioEnabled = true,
    ) {}

    /** @return Collection<int, LibraryStatistic> */
    public function getStatistics(User $user): Collection
    {
        $statistics = collect([
            LibraryStatistic::make('songs', 'Songs', $this->librarySummaryService->getSongCount($user)),
            LibraryStatistic::make('albums', 'Albums', $this->librarySummaryService->getAlbumCount($user)),
            LibraryStatistic::make('artists', 'Artists', $this->librarySummaryService->getArtistCount($user)),
        ]);

        if ($this->podcastsEnabled) {
            $statistics->push(
                LibraryStatistic::make('podcasts', 'Podcasts', $this->podcastRepository->countSubscribedByUser($user)),
                LibraryStatistic::make(
                    'episodes',
                    'Episodes',
                    $this->songRepository->countEpisodesSubscribedByUser($user),
                ),
            );
        }

        if ($this->radioEnabled) {
            $statistics->push(LibraryStatistic::make(
                'radio_stations',
                'Radio stations',
                $this->radioStationRepository->countAccessibleByUser($user),
            ));
        }

        if ($user->organization->ownedBy($user)) {
            $statistics->push(LibraryStatistic::make(
                'storage',
                'Storage used',
                $this->librarySummaryService->getTotalSongFileSize($user),
                LibraryStatisticUnit::BYTES,
            ));
        }

        return apply_filters(Filter::LIBRARY_STATISTICS, $statistics, $user)->values();
    }
}
