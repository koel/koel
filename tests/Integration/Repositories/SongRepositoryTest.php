<?php

namespace Tests\Integration\Repositories;

use App\Models\Genre;
use App\Models\Interaction;
use App\Models\Playlist;
use App\Models\Song;
use App\Repositories\Pagination\CursorStrategy;
use App\Repositories\SongRepository;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroupCollection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\sandbox_path;

class SongRepositoryTest extends TestCase
{
    private SongRepository $songRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->songRepository = app(SongRepository::class);
    }

    #[Test]
    public function getOneByPath(): void
    {
        $song = Song::factory()->createOne(['path' => 'foo']);
        self::assertSame($song->id, $this->songRepository->findOneByPath('foo')->id);
    }

    #[Test]
    public function findMtimeByPath(): void
    {
        Song::factory()->createOne(['path' => 'foo', 'mtime' => 1_700_000_000]);

        self::assertSame(1_700_000_000, $this->songRepository->findMtimeByPath('foo'));
        self::assertNull($this->songRepository->findMtimeByPath('bar'));
    }

    #[Test]
    public function getOneAccessibleWithoutRelations(): void
    {
        $song = Song::factory()->createOne();

        $foundSong = $this->songRepository->getOneAccessibleWithoutRelations($song->id, $song->owner);

        self::assertTrue($foundSong->is($song));
        self::assertSame([], $foundSong->getRelations());
    }

    #[Test]
    public function getManyByPaths(): void
    {
        $foo = Song::factory()->createOne(['path' => 'foo']);
        $bar = Song::factory()->createOne(['path' => 'bar']);
        Song::factory()->createOne(['path' => 'baz']);

        $songs = $this->songRepository->getManyByPaths(['foo', 'bar', 'qux']);

        self::assertEqualsCanonicalizing([$foo->id, $bar->id], $songs->modelKeys());
    }

    #[Test]
    public function getSmartPlaylistStats(): void
    {
        $user = create_user();
        $playlist = Playlist::factory()->smart()->createOne();
        Song::factory()->createMany([
            ['artist_name' => 'foo', 'length' => 100],
            ['artist_name' => 'foo', 'length' => 50.5],
            ['artist_name' => 'bar', 'length' => 1000],
        ]);

        self::assertSame(
            ['count' => 2, 'length' => 150.5],
            $this->songRepository->getSmartPlaylistStats($playlist, $user),
        );
    }

    #[Test]
    public function getSmartPlaylistStatsWithPlayCountRule(): void
    {
        $user = create_user();
        $playlist = self::smartPlaylistWithRules([[
            'model' => 'interactions.play_count',
            'operator' => 'isGreaterThan',
            'value' => [5],
        ]]);
        $played = Song::factory()->createOne(['length' => 200]);
        Song::factory()->createOne(['length' => 300]);
        Interaction::factory()->for($user)->for($played)->createOne(['play_count' => 10]);

        self::assertSame(
            ['count' => 1, 'length' => 200.0],
            $this->songRepository->getSmartPlaylistStats($playlist, $user),
        );
    }

    #[Test]
    public function getSmartPlaylistStatsCountsNoMoreSongsThanThePlaylistReturns(): void
    {
        $user = create_user();
        $playlist = Playlist::factory()->smart()->createOne();
        Song::factory()->count(501)->create(['artist_name' => 'foo', 'length' => 1]);

        self::assertSame(
            ['count' => 500, 'length' => 500.0],
            $this->songRepository->getSmartPlaylistStats($playlist, $user),
        );
    }

    #[Test]
    public function searchHydratesOnlyTheReturnedSongsInRankedOrder(): void
    {
        File::ensureDirectoryExists(sandbox_path('search-indexes'));

        config([
            'scout.driver' => 'tntsearch',
            'scout.tntsearch.storage' => sandbox_path('search-indexes'),
        ]);

        $user = create_user();
        Song::factory()->for($user, 'owner')->state(['title' => 'Love Song'])->createMany(10);

        $expectedIds = Song::search('love')->keys()->take(3)->all();

        $hydratedSongCount = 0;
        Song::retrieved(static function () use (&$hydratedSongCount): void {
            $hydratedSongCount++;
        });

        $results = $this->songRepository->search('love', 3, $user);

        self::assertSame($expectedIds, $results->pluck('id')->all());
        self::assertSame(3, $hydratedSongCount);
    }

    #[Test]
    public function getByGenreRandomlyReturnsDistinctSongsFromTheGenreOnly(): void
    {
        $rock = Genre::factory()->has(Song::factory()->count(5))->createOne();
        Genre::factory()->has(Song::factory()->count(5))->createOne();
        Song::factory()->createMany(5);

        $songIds = $this->songRepository->getByGenre($rock, 3, random: true, scopedUser: create_user())->pluck('id');

        self::assertCount(3, $songIds->unique());
        self::assertEmpty($songIds->diff($rock->songs->pluck('id')));
    }

    #[Test]
    public function getByGenreRandomlyReturnsSongsWithoutGenreWhenNoGenreIsGiven(): void
    {
        Genre::factory()->has(Song::factory()->count(5))->createOne();
        $songsWithoutGenre = Song::factory()->createMany(3);

        $songs = $this->songRepository->getByGenre(null, 10, random: true, scopedUser: create_user());

        self::assertEqualsCanonicalizing($songsWithoutGenre->pluck('id')->all(), $songs->pluck('id')->all());
    }

    #[Test]
    public function getByGenreRandomlyReturnsDistinctSongsWithoutGenreUpToTheLimit(): void
    {
        Genre::factory()->has(Song::factory()->count(5))->createOne();
        $songsWithoutGenre = Song::factory()->createMany(5);

        $songIds = $this->songRepository->getByGenre(null, 3, random: true, scopedUser: create_user())->pluck('id');

        self::assertCount(3, $songIds->unique());
        self::assertEmpty($songIds->diff($songsWithoutGenre->pluck('id')));
    }

    #[Test]
    public function getByGenreReturnsSongsWithoutGenreByTitleWhenNoGenreIsGiven(): void
    {
        Genre::factory()->has(Song::factory()->count(2))->createOne();
        $bravo = Song::factory()->createOne(['title' => 'Bravo']);
        $alpha = Song::factory()->createOne(['title' => 'Alpha']);

        $songs = $this->songRepository->getByGenre(null, 10, scopedUser: create_user());

        self::assertSame([$alpha->id, $bravo->id], $songs->pluck('id')->all());
    }

    #[Test]
    public function paginateByGenreWalksSongsWithoutGenreByCursorWhenNoGenreIsGiven(): void
    {
        Genre::factory()->has(Song::factory()->count(2))->createOne();
        $songsWithoutGenre = Song::factory()
            ->sequence(static fn (Sequence $sequence): array => ['title' => sprintf('Song %02d', $sequence->index)])
            ->createMany(51);

        $user = create_user();

        $firstPage = $this->songRepository->paginateByGenre(null, ['title'], 'asc', new CursorStrategy(null), $user);

        $secondPage = $this->songRepository->paginateByGenre(
            null,
            ['title'],
            'asc',
            new CursorStrategy($firstPage->nextCursor()->encode()),
            $user,
        );

        self::assertSame(
            $songsWithoutGenre->pluck('id')->all(),
            collect($firstPage->items())->concat($secondPage->items())->pluck('id')->all(),
        );
    }

    /** @param array<array<string, mixed>> $rules */
    private static function smartPlaylistWithRules(array $rules): Playlist
    {
        return Playlist::factory()->createOne([
            'rules' => SmartPlaylistRuleGroupCollection::create([
                [
                    'id' => (string) Str::uuid(),
                    'rules' => array_map(
                        static fn (array $rule): array => $rule + ['id' => (string) Str::uuid()],
                        $rules,
                    ),
                ],
            ]),
        ]);
    }
}
