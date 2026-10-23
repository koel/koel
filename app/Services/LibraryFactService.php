<?php

namespace App\Services;

use App\Enums\LibraryFactUnit;
use App\Hooks\Filter;
use App\Models\User;
use App\Repositories\PodcastRepository;
use App\Repositories\RadioStationRepository;
use App\Repositories\SongRepository;
use App\Values\Statistics\LibraryFact;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Collection;

class LibraryFactService
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

    /** @return Collection<int, LibraryFact> */
    public function getFacts(User $user): Collection
    {
        $facts = collect([
            LibraryFact::make('songs', 'Songs', $this->librarySummaryService->getSongCount($user)),
            LibraryFact::make('albums', 'Albums', $this->librarySummaryService->getAlbumCount($user)),
            LibraryFact::make('artists', 'Artists', $this->librarySummaryService->getArtistCount($user)),
        ]);

        if ($this->podcastsEnabled) {
            $facts->push(
                LibraryFact::make('podcasts', 'Podcasts', $this->podcastRepository->countSubscribedByUser($user)),
                LibraryFact::make('episodes', 'Episodes', $this->songRepository->countEpisodesSubscribedByUser($user)),
            );
        }

        if ($this->radioEnabled) {
            $facts->push(LibraryFact::make(
                'radio_stations',
                'Radio stations',
                $this->radioStationRepository->countAccessibleByUser($user),
            ));
        }

        if ($user->organization->ownedBy($user)) {
            $facts->push(LibraryFact::make(
                'storage',
                'Storage used',
                $this->librarySummaryService->getTotalSongFileSize($user),
                LibraryFactUnit::BYTES,
            ));
        }

        return apply_filters(Filter::LIBRARY_FACTS, $facts, $user)->values();
    }
}
