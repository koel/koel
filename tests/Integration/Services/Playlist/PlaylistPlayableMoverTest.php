<?php

namespace Tests\Integration\Services\Playlist;

use App\Enums\Placement;
use App\Models\Playlist;
use App\Models\Song;
use App\Repositories\PlaylistRepository;
use App\Services\Playlist\PlaylistPlayableMover;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_playlist;

class PlaylistPlayableMoverTest extends TestCase
{
    private PlaylistPlayableMover $mover;

    public function setUp(): void
    {
        parent::setUp();

        $this->mover = app(PlaylistPlayableMover::class);
    }

    #[Test]
    public function moveSongsFromBothSidesOfTheTarget(): void
    {
        $playlist = create_playlist();
        $ids = Song::factory()->createMany(8)->modelKeys();
        $playlist->addPlayables($ids);

        $this->mover->movePlayables($playlist, [$ids[6], $ids[1]], $ids[4], Placement::BEFORE);

        self::assertSame(
            [$ids[0], $ids[2], $ids[3], $ids[6], $ids[1], $ids[4], $ids[5], $ids[7]],
            self::getOrderedIds($playlist, $ids),
        );

        self::assertSame(range(1, 8), self::getSortedPositions($playlist, $ids));
    }

    #[Test]
    public function moveSongsAfterTheTarget(): void
    {
        $playlist = create_playlist();
        $ids = Song::factory()->createMany(6)->modelKeys();
        $playlist->addPlayables($ids);

        $this->mover->movePlayables($playlist, [$ids[0], $ids[5]], $ids[2], Placement::AFTER);

        self::assertSame([$ids[1], $ids[2], $ids[0], $ids[5], $ids[3], $ids[4]], self::getOrderedIds($playlist, $ids));

        self::assertSame(range(1, 6), self::getSortedPositions($playlist, $ids));
    }

    #[Test]
    public function ignoreSongsNotInThePlaylist(): void
    {
        $playlist = create_playlist();
        $ids = Song::factory()->createMany(3)->modelKeys();
        $playlist->addPlayables($ids);
        $outsiderId = Song::factory()->createOne()->id;

        $this->mover->movePlayables($playlist, [$outsiderId, $ids[2]], $ids[0], Placement::BEFORE);

        self::assertSame([$ids[2], $ids[0], $ids[1]], $playlist->refresh()->playables->modelKeys());
    }

    #[Test]
    public function moveDuplicatedSongIdsOnce(): void
    {
        $playlist = create_playlist();
        $ids = Song::factory()->createMany(4)->modelKeys();
        $playlist->addPlayables($ids);

        $this->mover->movePlayables($playlist, [$ids[0], $ids[0]], $ids[3], Placement::BEFORE);

        self::assertSame([$ids[1], $ids[2], $ids[0], $ids[3]], self::getOrderedIds($playlist, $ids));

        self::assertSame(range(1, 4), self::getSortedPositions($playlist, $ids));
    }

    /**
     * @param array<string> $ids
     *
     * @return array<string>
     */
    private static function getOrderedIds(Playlist $playlist, array $ids): array
    {
        return app(PlaylistRepository::class)->getPlayablePositionsInPlaylist($playlist, $ids)->sort()->keys()->all();
    }

    /**
     * @param array<string> $ids
     *
     * @return array<int>
     */
    private static function getSortedPositions(Playlist $playlist, array $ids): array
    {
        return app(PlaylistRepository::class)->getPlayablePositionsInPlaylist($playlist, $ids)->sort()->values()->all();
    }
}
