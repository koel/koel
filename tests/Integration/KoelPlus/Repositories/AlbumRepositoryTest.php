<?php

namespace Tests\Integration\KoelPlus\Repositories;

use App\Models\Album;
use App\Models\Interaction;
use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class AlbumRepositoryTest extends PlusTestCase
{
    private AlbumRepository $albumRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->albumRepository = app(AlbumRepository::class);
    }

    #[Test]
    public function getMostPlayedReturnsOnlyPlayedAccessibleAlbumsInPlayCountOrder(): void
    {
        $user = create_user();
        $leastPlayed = self::createPlayedAlbum(owner: $user, listener: $user, playCount: 1);
        $mostPlayed = self::createPlayedAlbum(owner: $user, listener: $user, playCount: 3);
        $secondMostPlayed = self::createPlayedAlbum(owner: $user, listener: $user, playCount: 2);
        Album::factory()->for($user)->has(Song::factory()->private())->createOne();

        $someoneElse = create_user();

        for ($i = 0; $i < 3; $i++) {
            self::createPlayedAlbum(owner: $someoneElse, listener: $user, playCount: 10);
        }

        $albums = $this->albumRepository->getMostPlayed(4, user: $user);

        self::assertSame([$mostPlayed->id, $secondMostPlayed->id, $leastPlayed->id], $albums->pluck('id')->all());
    }

    #[Test]
    public function getRecentlyAddedReturnsOnlyAccessibleAlbumsNewestFirst(): void
    {
        $user = create_user();
        $oldest = Album::factory()->for($user)->createOne(['created_at' => now()->subDays(3)]);
        $newest = Album::factory()->for($user)->createOne(['created_at' => now()->subDay()]);
        $middle = Album::factory()->for($user)->createOne(['created_at' => now()->subDays(2)]);

        Album::factory()->for(create_user())->createMany(3);

        $albums = $this->albumRepository->getRecentlyAdded(3, user: $user);

        self::assertSame([$newest->id, $middle->id, $oldest->id], $albums->pluck('id')->all());
    }

    #[Test]
    public function getRandomReturnsOnlyAccessibleAlbums(): void
    {
        $user = create_user();
        $ownAlbums = Album::factory()->for($user)->has(Song::factory())->createMany(3);

        Album::factory()->for(create_user())->has(Song::factory()->private())->createMany(10);

        $albums = $this->albumRepository->getRandom(3, $user);

        self::assertEqualsCanonicalizing($ownAlbums->pluck('id')->all(), $albums->pluck('id')->all());
    }

    #[Test]
    public function getRandomReturnsTheRequestedNumberOfAlbumsWhenOneAlbumHasManySongs(): void
    {
        $user = create_user();
        $albumWithManySongs = Album::factory()->for($user)->has(Song::factory()->count(60))->createOne();
        $albumsWithOneSong = Album::factory()->for($user)->has(Song::factory())->createMany(2);

        $albums = $this->albumRepository->getRandom(3, $user);

        self::assertEqualsCanonicalizing(
            [$albumWithManySongs->id, ...$albumsWithOneSong->pluck('id')->all()],
            $albums->pluck('id')->all(),
        );
    }

    private static function createPlayedAlbum(User $owner, User $listener, int $playCount): Album
    {
        $album = Album::factory()->for($owner)->createOne();
        $song = Song::factory()->for($album)->private()->createOne();

        Interaction::factory()->for($listener)->for($song)->createOne(['play_count' => $playCount]);

        return $album;
    }
}
