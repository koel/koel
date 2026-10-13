<?php

namespace Tests\Integration\Repositories;

use App\Models\Interaction;
use App\Models\Playlist;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroupCollection;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

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
