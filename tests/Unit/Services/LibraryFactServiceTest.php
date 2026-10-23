<?php

namespace Tests\Unit\Services;

use App\Enums\LibraryFactUnit;
use App\Hooks\Filter;
use App\Models\User;
use App\Repositories\PodcastRepository;
use App\Repositories\RadioStationRepository;
use App\Repositories\SongRepository;
use App\Services\LibraryFactService;
use App\Services\LibrarySummaryService;
use App\Values\Statistics\LibraryFact;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class LibraryFactServiceTest extends TestCase
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

    private function makeService(bool $podcastsEnabled = true, bool $radioEnabled = true): LibraryFactService
    {
        return new LibraryFactService(
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
        $facts = $this->makeService(podcastsEnabled: false, radioEnabled: false)->getFacts($this->user);

        self::assertSame(['songs', 'albums', 'artists'], $facts->pluck('key')->all());
    }

    #[Test]
    public function letTheListBeChangedThroughTheFilter(): void
    {
        $this->user->organization->claimOwnership($this->user);

        add_filter(Filter::LIBRARY_FACTS, static fn (Collection $facts, User $user) => $facts
            ->reject(static fn (LibraryFact $fact) => $fact->key === 'episodes')
            ->map(static fn (LibraryFact $fact) => $fact->key === 'storage'
                ? LibraryFact::make('storage', 'Storage used', 4_096, LibraryFactUnit::BYTES, used: 4_096, limit: 8_192)
                : $fact)
            ->prepend(LibraryFact::make('members', 'Members', $user->id)));

        $facts = $this->makeService()->getFacts($this->user);

        self::assertSame(
            ['members', 'songs', 'albums', 'artists', 'podcasts', 'radio_stations', 'storage'],
            $facts->pluck('key')->all(),
        );
        self::assertSame(8_192, $facts->last()->limit);
    }
}
