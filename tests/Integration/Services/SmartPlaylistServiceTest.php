<?php

namespace Tests\Integration\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Interaction;
use App\Models\Playlist;
use App\Models\Rule;
use App\Models\Song;
use App\Models\User;
use App\Services\SmartPlaylistService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class SmartPlaylistServiceTest extends TestCase
{
    /** @var SmartPlaylistService */
    private $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(SmartPlaylistService::class);
        Carbon::setTestNow(new Carbon('2018-07-15'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function provideRules(): array
    {
        return [
            [
                [
                    [
                        'logic' => 'and',
                        'model' => 'title',
                        'operator' => 'beginsWith',
                        'value' => ['Foo'],
                    ],
                ],
                'select * from "songs" where "title" LIKE ?',
                ['Foo%'],
            ],
            [
                [
                    [
                        'logic' => 'and',
                        'model' => 'title',
                        'operator' => 'endsWith',
                        'value' => ['Foo'],
                    ],
                ],
                'select * from "songs" where "title" LIKE ?',
                ['%Foo'],
            ],
            [
                [
                    [
                        'logic' => 'or',
                        'model' => 'title',
                        'operator' => 'is',
                        'value' => ['Foo'],
                    ],
                ],
                'select * from "songs" where "title" = ?',
                ['Foo'],
            ],
            [
                [
                    [
                        'logic' => 'or',
                        'model' => 'title',
                        'operator' => 'isNot',
                        'value' => ['Foo'],
                    ],
                ],
                'select * from "songs" where "title" <> ?',
                ['Foo'],
            ],
            [
                [
                    [
                        'logic' => 'and',
                        'model' => 'title',
                        'operator' => 'contains',
                        'value' => ['Foo'],
                    ],
                ],
                'select * from "songs" where "title" LIKE ?',
                ['%Foo%'],
            ],
            [
                [
                    [
                        'logic' => 'and',
                        'model' => 'title',
                        'operator' => 'notContain',
                        'value' => ['Foo'],
                    ],
                ],
                'select * from "songs" where "title" NOT LIKE ?',
                ['%Foo%'],
            ],
            [
                [
                    [
                        'logic' => 'and',
                        'model' => 'length',
                        'operator' => 'isGreaterThan',
                        'value' => [100],
                    ],
                ],
                'select * from "songs" where "length" > ?',
                [100],
            ],
            [
                [
                    [
                        'logic' => 'and',
                        'model' => 'length',
                        'operator' => 'isLessThan',
                        'value' => [100],
                    ],
                ],
                'select * from "songs" where "length" < ?',
                [100],
            ],
            [
                [
                    [
                        'logic' => 'and',
                        'model' => 'length',
                        'operator' => 'isBetween',
                        'value' => [100, 200],
                    ],
                ],
                'select * from "songs" where "length" between ? and ?',
                [100, 200],
            ],
            [
                [
                    [
                        'logic' => 'and',
                        'model' => 'created_at',
                        'operator' => 'inLast',
                        'value' => [7],
                    ],
                ],
                'select * from "songs" where "created_at" >= ?',
                ['2018-07-08 00:00:00'],
            ],
            [
                [
                    [
                        'logic' => 'and',
                        'model' => 'created_at',
                        'operator' => 'notInLast',
                        'value' => [7],
                    ],
                ],
                'select * from "songs" where "created_at" < ?',
                ['2018-07-08 00:00:00'],
            ],
            [
                [
                    [
                        'logic' => 'and',
                        'model' => 'created_at',
                        'operator' => 'notInLast',
                        'value' => [7],
                    ],
                    [
                        'logic' => 'or',
                        'model' => 'length',
                        'operator' => 'isBetween',
                        'value' => [100, 200],
                    ],
                ],
                'select * from "songs" where "created_at" < ? or "length" between ? and ?',
                ['2018-07-08 00:00:00', 100, 200],
            ],
            [
                [
                    [
                        'logic' => 'or',
                        'model' => 'artist.name',
                        'operator' => 'isNot',
                        'value' => ['Foo'],
                    ],
                    [
                        'logic' => 'and',
                        'model' => 'created_at',
                        'operator' => 'notInLast',
                        'value' => [7],
                    ],
                ],
                'select * from "songs" where exists (select * from "artists" where "songs"."artist_id" = "artists"."id" or ("name" <> ?)) and "created_at" < ?',
                ['Foo', '2018-07-08 00:00:00', 100, 200],
            ],
        ];
    }

    /**
     * @dataProvider provideRules
     *
     * @param string[] $rules
     * @param mixed[]  $bindings
     */
    public function testBuildQueryForRules(array $rules, string $sql, array $bindings): void
    {
        $query = $this->service->buildQueryForRules($rules);
        $this->assertSame($sql, $query->toSql());
        $queryBinding = $query->getBindings();

        for ($i = 0, $count = count($queryBinding); $i < $count; $i++) {
            $this->assertSame(
                $bindings[$i],
                is_object($queryBinding[$i]) ? (string) $queryBinding[$i] : $queryBinding[$i]
            );
        }
    }

    public function testAllOperatorsAreCovered(): void
    {
        $operators = collect($this->provideRules())->map(static function (array $providedRules): string {
            return $providedRules[0][0]['operator'];
        });

        $this->assertSame(count(Rule::VALID_OPERATORS), $operators->unique()->count());
    }

    public function testRuleWithoutSubQueries(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        factory(Song::class, 2)->create([
            'title' => static function (): string {
                return 'Unique Foo '.uniqid();
            },
        ]);

        factory(Song::class, 5)->create([
            'title' => 'Has nothing to do with the rule',
        ]);

        $playlist = factory(Playlist::class)->create([
            'user_id' => $user->id,
            'rules' => [
                [
                    'logic' => 'and',
                    'model' => 'title',
                    'operator' => 'beginsWith',
                    'value' => ['Unique Foo'],
                ],
            ],
        ]);

        $songs = $this->service->getSongs($playlist);
        $this->assertCount(2, $songs);
        $songs->each(function (Song $song): void {
            $this->assertStringStartsWith('Unique Foo', $song->title);
        });
    }

    public function testRulesWithSubQuery(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        /** @var Album[] $albums */
        $albums = factory(Album::class, 3)->create([
            'artist_id' => static function (): int {
                return factory(Artist::class)->create([
                    'name' => static function (): string {
                        return 'Foo Artist '.uniqid();
                    },
                ])->id;
            },
        ]);

        foreach ($albums as $album) {
            factory(Song::class, 2)->create([
                'album_id' => $album->id,
                'artist_id' => $album->artist->id,
            ]);
        }

        /** @var Album $inApplicableAlbum */
        $inApplicableAlbum = factory(Album::class)->create([
            'artist_id' => factory(Artist::class)->create(['name' => 'Nothing to do with the rule']),
        ]);

        factory(Song::class, 1)->create([
            'album_id' => $inApplicableAlbum->id,
        ]);

        $playlist = factory(Playlist::class)->create([
            'user_id' => $user->id,
            'rules' => [
                [
                    'logic' => 'and',
                    'model' => 'artist.name',
                    'operator' => 'beginsWith',
                    'value' => ['Foo Artist'],
                ],
                [
                    'logic' => 'or',
                    'model' => 'title',
                    'operator' => 'beginsWith',
                    'value' => ['There should not be anything like this'],
                ],
            ],
        ]);

        $songs = $this->service->getSongs($playlist);
        $this->assertCount(6, $songs);
        $songs->each(function (Song $song): void {
            $this->assertStringStartsWith('Foo Artist', $song->artist->name);
        });
    }

    public function testRulesWithUser(): void
    {
        /**
         * @var User
         * @var User $alice
         */
        $bob = factory(User::class)->create();
        $alice = factory(User::class)->create();

        $bobSong = factory(Song::class)->create([
            'title' => 'Song for Bob',
        ]);
        factory(Interaction::class)->create([
            'user_id' => $bob->id,
            'song_id' => $bobSong->id,
            'play_count' => 10,
        ]);

        $aliceSong = factory(Song::class)->create([
            'title' => 'Song for Alice',
        ]);
        factory(Interaction::class)->create([
            'user_id' => $alice->id,
            'song_id' => $aliceSong->id,
            'play_count' => 12,
        ]);

        $playlist = factory(Playlist::class)->create([
            'user_id' => $bob->id,
            'rules' => [
                [
                    'logic' => 'and',
                    'model' => 'interactions.play_count',
                    'operator' => 'isGreaterThan',
                    'value' => [5],
                ],
            ],
        ]);

        /** @var Song[]|Collection $songs */
        $songs = $this->service->getSongs($playlist);
        $this->assertCount(1, $songs);
        $this->assertSame('Song for Bob', $songs[0]->title);
    }
}
