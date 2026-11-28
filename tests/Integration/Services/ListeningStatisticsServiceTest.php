<?php

namespace Tests\Integration\Services;

use App\Enums\ListeningPeriod;
use App\Models\Play;
use App\Models\Song;
use App\Services\InteractionService;
use App\Services\LibrarySummaryService;
use App\Services\ListeningStatisticsService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class ListeningStatisticsServiceTest extends TestCase
{
    private ListeningStatisticsService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(ListeningStatisticsService::class);
    }

    #[Test]
    public function statisticsAreCachedUntilTheUserPlaysAgain(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne();

        Play::factory()->for($user)->for($song)->createOne();

        $first = $this->service->getStatistics($user, ListeningPeriod::ALL_TIME, 'UTC');
        self::assertSame(1, $first->summary->plays);

        Play::factory()->for($user)->for($song)->createOne();

        $cached = $this->service->getStatistics($user, ListeningPeriod::ALL_TIME, 'UTC');
        self::assertSame(1, $cached->summary->plays);

        app(InteractionService::class)->increasePlayCount($song, $user);

        self::assertSame(3, $this->service->getStatistics($user, ListeningPeriod::ALL_TIME, 'UTC')->summary->plays);
    }

    #[Test]
    public function statisticsAreRecomputedWhenTheLibraryChanges(): void
    {
        $user = create_user();

        Play::factory()->for($user)->createOne();

        self::assertSame(1, $this->service->getStatistics($user, ListeningPeriod::ALL_TIME, 'UTC')->summary->plays);

        Play::factory()->for($user)->createOne();
        app(LibrarySummaryService::class)->markLibraryChanged();

        self::assertSame(2, $this->service->getStatistics($user, ListeningPeriod::ALL_TIME, 'UTC')->summary->plays);
    }
}
