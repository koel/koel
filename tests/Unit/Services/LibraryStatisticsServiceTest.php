<?php

namespace Tests\Unit\Services;

use App\Enums\LibraryStatisticUnit;
use App\Hooks\Filter;
use App\Models\User;
use App\Repositories\PodcastRepository;
use App\Repositories\RadioStationRepository;
use App\Repositories\SongRepository;
use App\Services\LibraryStatisticsService;
use App\Services\LibrarySummaryService;
use App\Values\Statistics\LibraryStatistic;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_guest;
use function Tests\create_user;

class LibraryStatisticsServiceTest extends TestCase
{
    private LibrarySummaryService|MockInterface $librarySummaryService;
    private SongRepository|MockInterface $songRepository;
    private PodcastRepository|MockInterface $podcastRepository;
    private RadioStationRepository|MockInterface $radioStationRepository;
    private User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->librarySummaryService = Mockery::mock(LibrarySummaryService::class);
        $this->songRepository = Mockery::mock(SongRepository::class);
        $this->podcastRepository = Mockery::mock(PodcastRepository::class);
        $this->radioStationRepository = Mockery::mock(RadioStationRepository::class);
        $this->user = create_user();

        $this->librarySummaryService->allows('getSongCount')->with($this->user)->andReturn(10);
        $this->librarySummaryService->allows('getAlbumCount')->with($this->user)->andReturn(3);
        $this->librarySummaryService->allows('getArtistCount')->with($this->user)->andReturn(2);
        $this->librarySummaryService->allows('getTotalSongFileSize')->with($this->user)->andReturn(4_096);
        $this->podcastRepository->allows('countSubscribedByUser')->with($this->user)->andReturn(1);
        $this->songRepository->allows('countEpisodesSubscribedByUser')->with($this->user)->andReturn(5);
        $this->radioStationRepository->allows('countAccessibleByUser')->with($this->user)->andReturn(4);
    }

    private function makeService(bool $podcastsEnabled = true, bool $radioEnabled = true): LibraryStatisticsService
    {
        return new LibraryStatisticsService(
            $this->librarySummaryService,
            $this->songRepository,
            $this->podcastRepository,
            $this->radioStationRepository,
            $podcastsEnabled,
            $radioEnabled,
        );
    }

    #[Test]
    public function leaveOutPodcastsAndRadioWhenDisabled(): void
    {
        $statistics = $this->makeService(podcastsEnabled: false, radioEnabled: false)->getStatistics($this->user);

        self::assertSame(['songs', 'albums', 'artists'], $statistics->pluck('key')->all());
    }

    #[Test]
    public function letTheListBeChangedThroughTheFilter(): void
    {
        $this->user->organization->claimOwnership($this->user);

        add_filter(Filter::LIBRARY_STATISTICS, static fn (Collection $statistics, User $user) => $statistics
            ->reject(static fn (LibraryStatistic $statistic) => $statistic->key === 'episodes')
            ->map(static fn (LibraryStatistic $statistic) => $statistic->key === 'storage'
                ? LibraryStatistic::make(
                    'storage',
                    'Storage used',
                    4_096,
                    LibraryStatisticUnit::BYTES,
                    used: 4_096,
                    limit: 8_192,
                )
                : $statistic)
            ->prepend(LibraryStatistic::make('members', 'Members', $user->id)));

        $statistics = $this->makeService()->getStatistics($this->user);

        self::assertSame(
            ['members', 'songs', 'albums', 'artists', 'podcasts', 'radio_stations', 'storage'],
            $statistics->pluck('key')->all(),
        );
        self::assertSame(8_192, $statistics->last()->limit);
    }

    #[Test]
    public function giveGuestsNoStatisticsEvenThroughTheFilter(): void
    {
        add_filter(Filter::LIBRARY_STATISTICS, static fn (Collection $statistics) => $statistics->push(LibraryStatistic::make(
            'members',
            'Members',
            1,
        )));

        self::assertTrue($this->makeService()->getStatistics(create_guest())->isEmpty());
    }
}
