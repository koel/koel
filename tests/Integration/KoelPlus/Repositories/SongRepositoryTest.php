<?php

namespace Tests\Integration\KoelPlus\Repositories;

use App\Enums\PlayableType;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Interaction;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\PlusTestCase;

use function Tests\create_user;

class SongRepositoryTest extends PlusTestCase
{
    #[Test]
    public function searchFindsOwnSongsRankedBehindOtherUsersPrivateOnes(): void
    {
        config(['scout.driver' => 'collection']);

        $user = create_user();
        $ownSong = Song::factory()->for($user, 'owner')->createOne([
            'id' => '00000000-0000-0000-0000-000000000000',
            'title' => 'Love Me Tender',
        ]);

        Song::factory()->for(create_user(), 'owner')->private()->state(['title' => 'Love Hurts'])->createMany(5);

        $results = app(SongRepository::class)->search('love', 5, $user);

        self::assertTrue($results->sole()->is($ownSong));
    }

    #[Test]
    public function getRandomReturnsOnlyAccessibleSongs(): void
    {
        $user = create_user();
        $ownSongs = Song::factory()->for($user, 'owner')->private()->createMany(3);

        Song::factory()->for(create_user(), 'owner')->private()->createMany(10);

        $songs = app(SongRepository::class)->getRandom(3, $user);

        self::assertEqualsCanonicalizing($ownSongs->pluck('id')->all(), $songs->pluck('id')->all());
    }

    #[Test]
    public function getByGenreRandomlyReturnsOnlyAccessibleSongs(): void
    {
        $user = create_user();
        $rock = Genre::factory()->createOne();
        $ownSongs = Song::factory()->for($user, 'owner')->private()->hasAttached($rock)->createMany(3);

        Song::factory()->for(create_user(), 'owner')->private()->hasAttached($rock)->createMany(10);

        $songs = app(SongRepository::class)->getByGenre($rock, 3, random: true, scopedUser: $user);

        self::assertEqualsCanonicalizing($ownSongs->pluck('id')->all(), $songs->pluck('id')->all());
    }

    #[Test]
    #[TestWith([3])]
    #[TestWith([10])]
    public function getByGenreRandomlyReturnsOnlyAccessibleSongsWithoutGenre(int $limit): void
    {
        $user = create_user();
        $ownSongs = Song::factory()->for($user, 'owner')->private()->createMany(3);

        Song::factory()->for(create_user(), 'owner')->private()->createMany(10);

        $songs = app(SongRepository::class)->getByGenre(null, $limit, random: true, scopedUser: $user);

        self::assertEqualsCanonicalizing($ownSongs->pluck('id')->all(), $songs->pluck('id')->all());
    }

    #[Test]
    public function getRecentlyAddedReturnsOnlyAccessibleSongsNewestFirst(): void
    {
        $user = create_user();
        $oldest = Song::factory()
            ->for($user, 'owner')
            ->private()
            ->createOne(['created_at' => now()->subDays(3)]);
        $newest = Song::factory()->for($user, 'owner')->private()->createOne(['created_at' => now()->subDay()]);
        $middle = Song::factory()
            ->for($user, 'owner')
            ->private()
            ->createOne(['created_at' => now()->subDays(2)]);

        Song::factory()->for(create_user(), 'owner')->private()->createMany(3);

        $songs = app(SongRepository::class)->getRecentlyAdded(3, $user);

        self::assertSame([$newest->id, $middle->id, $oldest->id], $songs->pluck('id')->all());
    }

    #[Test]
    public function getLeastPlayedReturnsOnlyAccessibleSongsInPlayCountOrder(): void
    {
        $user = create_user();
        $mostPlayed = Song::factory()->for($user, 'owner')->private()->createOne();
        $leastPlayed = Song::factory()->for($user, 'owner')->private()->createOne();
        $secondLeastPlayed = Song::factory()->for($user, 'owner')->private()->createOne();

        Interaction::factory()->for($user)->for($mostPlayed)->createOne(['play_count' => 3]);
        Interaction::factory()->for($user)->for($leastPlayed)->createOne(['play_count' => 1]);
        Interaction::factory()->for($user)->for($secondLeastPlayed)->createOne(['play_count' => 2]);

        Song::factory()->for(create_user(), 'owner')->private()->createMany(3);

        $songs = app(SongRepository::class)->getLeastPlayed(3, $user, PlayableType::SONG);

        self::assertSame([$leastPlayed->id, $secondLeastPlayed->id, $mostPlayed->id], $songs->pluck('id')->all());
    }

    #[Test]
    public function getLeastPlayedListsUnplayedSongsBeforePlayedOnes(): void
    {
        $user = create_user();
        $played = Song::factory()->for($user, 'owner')->private()->createOne();
        $unplayed = Song::factory()->for($user, 'owner')->private()->createOne();

        Interaction::factory()->for($user)->for($played)->createOne(['play_count' => 1]);

        $songs = app(SongRepository::class)->getLeastPlayed(2, $user, PlayableType::SONG);

        self::assertSame([$unplayed->id, $played->id], $songs->pluck('id')->all());
    }

    #[Test]
    public function getSimilarToManyReturnsOnlyAccessibleSongs(): void
    {
        $user = create_user();
        $artist = Artist::factory()->createOne();
        $playedSong = Song::factory()->for($user, 'owner')->for($artist)->private()->createOne();
        $ownSimilarSongs = Song::factory()->for($user, 'owner')->for($artist)->private()->createMany(3);

        Song::factory()->for(create_user(), 'owner')->for($artist)->private()->createMany(10);

        $songs = app(SongRepository::class)->getSimilar($playedSong, 3, $user);

        self::assertEqualsCanonicalizing($ownSimilarSongs->pluck('id')->all(), $songs->pluck('id')->all());
    }

    #[Test]
    public function getRandomLeavesOutHiddenSongs(): void
    {
        $user = create_user();
        $visibleSongs = Song::factory()->for($user, 'owner')->createMany(2);
        self::hideSongs($user, Song::factory()->for($user, 'owner')->createMany(2));

        $songs = app(SongRepository::class)->getRandom(4, $user);

        self::assertEqualsCanonicalizing($visibleSongs->modelKeys(), $songs->modelKeys());
    }

    #[Test]
    public function getRandomInGenreAndYearsLeavesOutHiddenSongs(): void
    {
        $user = create_user();
        $visibleSongs = Song::factory()->for($user, 'owner')->createMany(2);
        self::hideSongs($user, Song::factory()->for($user, 'owner')->createMany(2));

        $songs = app(SongRepository::class)->getRandomInGenreAndYears(4, null, null, null, $user);

        self::assertEqualsCanonicalizing($visibleSongs->modelKeys(), $songs->modelKeys());
    }

    #[Test]
    public function getByGenreRandomlyLeavesOutHiddenSongs(): void
    {
        $user = create_user();
        $rock = Genre::factory()->createOne();
        $visibleSongs = Song::factory()->for($user, 'owner')->hasAttached($rock)->createMany(2);
        self::hideSongs($user, Song::factory()->for($user, 'owner')->hasAttached($rock)->createMany(2));

        $songs = app(SongRepository::class)->getByGenre($rock, 4, random: true, scopedUser: $user);

        self::assertEqualsCanonicalizing($visibleSongs->modelKeys(), $songs->modelKeys());
    }

    #[Test]
    #[TestWith([2])]
    #[TestWith([10])]
    public function getByGenreRandomlyLeavesOutHiddenSongsWithoutGenre(int $limit): void
    {
        $user = create_user();
        $visibleSongs = Song::factory()->for($user, 'owner')->createMany(2);
        self::hideSongs($user, Song::factory()->for($user, 'owner')->createMany(2));

        $songs = app(SongRepository::class)->getByGenre(null, $limit, random: true, scopedUser: $user);

        self::assertEqualsCanonicalizing($visibleSongs->modelKeys(), $songs->modelKeys());
    }

    #[Test]
    public function getSimilarLeavesOutHiddenSongs(): void
    {
        $user = create_user();
        $artist = Artist::factory()->createOne();
        $playedSong = Song::factory()->for($user, 'owner')->for($artist)->createOne();
        $visibleSongs = Song::factory()->for($user, 'owner')->for($artist)->createMany(2);
        self::hideSongs($user, Song::factory()->for($user, 'owner')->for($artist)->createMany(2));

        $songs = app(SongRepository::class)->getSimilar($playedSong, 4, $user);

        self::assertEqualsCanonicalizing($visibleSongs->modelKeys(), $songs->modelKeys());
    }

    #[Test]
    public function getByPlaylistLeavesOutHiddenSongsFromSmartPlaylist(): void
    {
        $user = create_user();
        $playlist = Playlist::factory()->smart()->createOne();
        $visibleSongs = Song::factory()->for($user, 'owner')->state(['artist_name' => 'foo'])->createMany(2);
        self::hideSongs($user, Song::factory()->for($user, 'owner')->state(['artist_name' => 'foo'])->createMany(2));

        $songs = app(SongRepository::class)->getByPlaylist($playlist, $user);

        self::assertEqualsCanonicalizing($visibleSongs->modelKeys(), $songs->modelKeys());
    }

    #[Test]
    public function getSmartPlaylistStatsLeavesOutHiddenSongs(): void
    {
        $user = create_user();
        $playlist = Playlist::factory()->smart()->createOne();
        Song::factory()->for($user, 'owner')->createOne(['artist_name' => 'foo', 'length' => 100]);
        self::hideSongs($user, Song::factory()->for($user, 'owner')->state(['artist_name' => 'foo'])->createMany(2));

        self::assertSame(
            ['count' => 1, 'length' => 100.0],
            app(SongRepository::class)->getSmartPlaylistStats($playlist, $user),
        );
    }

    #[Test]
    public function getForQueueLeavesOutHiddenSongs(): void
    {
        $user = create_user();
        $visibleSongs = Song::factory()->for($user, 'owner')->createMany(2);
        self::hideSongs($user, Song::factory()->for($user, 'owner')->createMany(2));

        $songs = app(SongRepository::class)->getForQueue(['title'], 'asc', 4, $user);

        self::assertEqualsCanonicalizing($visibleSongs->modelKeys(), $songs->modelKeys());
    }

    #[Test]
    public function getByAlbumKeepsHiddenSongsAndMarksThem(): void
    {
        $user = create_user();
        $visibleSong = Song::factory()->for($user, 'owner')->createOne();
        $hiddenSong = Song::factory()->for($user, 'owner')->for($visibleSong->album)->createOne();
        self::hideSongs($user, $hiddenSong->newCollection([$hiddenSong]));

        $songs = app(SongRepository::class)->getByAlbum($visibleSong->album, $user)->keyBy('id');

        self::assertFalse($songs[$visibleSong->id]->hidden);
        self::assertTrue($songs[$hiddenSong->id]->hidden);
    }

    /** @param Collection<int, Song> $songs */
    private static function hideSongs(User $user, Collection $songs): void
    {
        $user->hiddenSongs()->attach($songs->modelKeys());
    }
}
