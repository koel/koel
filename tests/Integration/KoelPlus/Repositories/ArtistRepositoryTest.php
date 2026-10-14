<?php

namespace Tests\Integration\KoelPlus\Repositories;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Interaction;
use App\Models\Song;
use App\Models\User;
use App\Repositories\ArtistRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class ArtistRepositoryTest extends PlusTestCase
{
    private ArtistRepository $artistRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->artistRepository = app(ArtistRepository::class);
    }

    #[Test]
    public function getMostPlayedReturnsOnlyPlayedAccessibleArtistsInPlayCountOrder(): void
    {
        $user = create_user();
        $leastPlayed = self::createPlayedArtist(owner: $user, listener: $user, playCount: 1);
        $mostPlayed = self::createPlayedArtist(owner: $user, listener: $user, playCount: 3);
        $secondMostPlayed = self::createPlayedArtist(owner: $user, listener: $user, playCount: 2);
        self::createArtistWithAlbum($user);

        $someoneElse = create_user();

        for ($i = 0; $i < 3; $i++) {
            self::createPlayedArtist(owner: $someoneElse, listener: $user, playCount: 10);
        }

        $artists = $this->artistRepository->getMostPlayed(4, $user);

        self::assertSame([$mostPlayed->id, $secondMostPlayed->id, $leastPlayed->id], $artists->pluck('id')->all());
    }

    #[Test]
    public function getRecentlyAddedReturnsOnlyAccessibleArtistsNewestFirst(): void
    {
        $user = create_user();
        $oldest = self::createArtistWithAlbum($user, ['created_at' => now()->subDays(3)]);
        $newest = self::createArtistWithAlbum($user, ['created_at' => now()->subDay()]);
        $middle = self::createArtistWithAlbum($user, ['created_at' => now()->subDays(2)]);

        $someoneElse = create_user();

        for ($i = 0; $i < 3; $i++) {
            self::createArtistWithAlbum($someoneElse);
        }

        $artists = $this->artistRepository->getRecentlyAdded(3, $user);

        self::assertSame([$newest->id, $middle->id, $oldest->id], $artists->pluck('id')->all());
    }

    #[Test]
    public function getRandomReturnsOnlyAccessibleArtists(): void
    {
        $user = create_user();
        $ownArtistIds = collect(range(1, 3))->map(static fn () => self::createArtistWithAlbum($user)->id);

        $someoneElse = create_user();

        for ($i = 0; $i < 10; $i++) {
            self::createArtistWithAlbum($someoneElse);
        }

        $artists = $this->artistRepository->getRandom(3, $user);

        self::assertEqualsCanonicalizing($ownArtistIds->all(), $artists->pluck('id')->all());
    }

    /** @param array<string, mixed> $attributes */
    private static function createArtistWithAlbum(User $owner, array $attributes = []): Artist
    {
        $artist = Artist::factory()->for($owner)->createOne($attributes);
        Album::factory()->for($owner)->for($artist)->createOne();

        return $artist;
    }

    private static function createPlayedArtist(User $owner, User $listener, int $playCount): Artist
    {
        $artist = self::createArtistWithAlbum($owner);
        $song = Song::factory()->for($artist->albums()->sole())->private()->createOne();

        Interaction::factory()->for($listener)->for($song)->createOne(['play_count' => $playCount]);

        return $artist;
    }
}
