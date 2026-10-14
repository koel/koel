<?php

namespace Tests\Integration\Services;

use App\Models\Album;
use App\Models\Artist;
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
    public function summariesAreCachedUntilLibraryChanges(): void
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

        $this->service->markLibraryChanged();

        self::assertSame(2, $this->service->getSongCount($user));
        self::assertEqualsWithDelta(150.0, $this->service->getTotalSongLength($user), 0.01);
        self::assertSame(2, $this->service->getGenreSummaries($user)->sole()->songCount);
    }

    #[Test]
    public function albumArtistsAreCachedUntilLibraryChanges(): void
    {
        $user = create_user();
        Album::factory()->for(Artist::factory()->createOne())->createOne();

        self::assertCount(1, $this->service->getAlbumArtistsWithoutFavoritesOrRatings($user));

        Album::factory()->for(Artist::factory()->createOne())->createOne();

        self::assertCount(1, $this->service->getAlbumArtistsWithoutFavoritesOrRatings($user));

        $this->service->markLibraryChanged();

        self::assertCount(2, $this->service->getAlbumArtistsWithoutFavoritesOrRatings($user));
    }
}
