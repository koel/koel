<?php

namespace App\Services;

use App\Enums\LibraryStatUnit;
use App\Hooks\Filter;
use App\Models\User;
use App\Repositories\PodcastRepository;
use App\Repositories\RadioStationRepository;
use App\Repositories\SongRepository;
use App\Values\Statistics\LibraryStat;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Collection;

class LibraryStatsService
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

    /** @return Collection<int, LibraryStat> */
    public function getStats(User $user): Collection
    {
        $stats = collect([
            LibraryStat::make('songs', 'Songs', $this->librarySummaryService->getSongCount($user)),
            LibraryStat::make('albums', 'Albums', $this->librarySummaryService->getAlbumCount($user)),
            LibraryStat::make('artists', 'Artists', $this->librarySummaryService->getArtistCount($user)),
        ]);

        if ($this->podcastsEnabled) {
            $stats->push(
                LibraryStat::make('podcasts', 'Podcasts', $this->podcastRepository->countSubscribedByUser($user)),
                LibraryStat::make('episodes', 'Episodes', $this->songRepository->countEpisodesSubscribedByUser($user)),
            );
        }

        if ($this->radioEnabled) {
            $stats->push(LibraryStat::make(
                'radio_stations',
                'Radio stations',
                $this->radioStationRepository->countAccessibleByUser($user),
            ));
        }

        if ($user->organization->ownedBy($user)) {
            $stats->push(LibraryStat::make(
                'storage',
                'Storage used',
                $this->librarySummaryService->getTotalSongFileSize($user),
                LibraryStatUnit::BYTES,
            ));
        }

        return apply_filters(Filter::LIBRARY_STATS, $stats, $user)->values();
    }
}
