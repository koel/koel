<?php

namespace Tests\Integration\Services;

use App\Models\Genre;
use App\Models\Song;
use App\Services\LibrarySummaryService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class LibrarySummaryServiceTest extends TestCase
{
    private LibrarySummaryService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(LibrarySummaryService::class);
    }

    #[Test]
    public function summariesAreCachedUntilInvalidated(): void
    {
        $user = create_user();
        $rock = Genre::factory()->createOne(['name' => 'Rock']);
        $rock->songs()->attach(Song::factory()->createOne(['length' => 100]));

        self::assertSame(1, $this->service->getSongCount($user));
        self::assertEqualsWithDelta(100.0, $this->service->getTotalSongLength($user), 0.01);
        self::assertSame(1, $this->service->getGenreSummaries($user)->sole()->songCount);

        $rock->songs()->attach(Song::factory()->createOne(['length' => 50]));

        self::assertSame(1, $this->service->getSongCount($user));
        self::assertEqualsWithDelta(100.0, $this->service->getTotalSongLength($user), 0.01);
        self::assertSame(1, $this->service->getGenreSummaries($user)->sole()->songCount);

        $this->service->invalidateSummaries();

        self::assertSame(2, $this->service->getSongCount($user));
        self::assertEqualsWithDelta(150.0, $this->service->getTotalSongLength($user), 0.01);
        self::assertSame(2, $this->service->getGenreSummaries($user)->sole()->songCount);
    }
}
