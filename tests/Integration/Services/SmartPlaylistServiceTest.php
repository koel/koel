<?php

namespace Tests\Integration\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Interaction;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Services\SmartPlaylistService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SmartPlaylistServiceTest extends TestCase
{
    private SmartPlaylistService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(SmartPlaylistService::class);
    }

    public function testTitleIs(): void
    {
        $matches = Song::factory()->count(3)->create(['title' => 'Foo Something']);
        Song::factory()->count(3)->create(['title' => 'Bar Something']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'title',
                        'operator' => 'is',
                        'value' => ['Foo Something'],
                    ],
                ],
            ],
        ]);
    }

    public function testTitleIsNot(): void
    {
        $matches = Song::factory()->count(3)->create(['title' => 'Foo Something']);
        Song::factory()->count(3)->create(['title' => 'Bar Something']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'title',
                        'operator' => 'isNot',
                        'value' => ['Bar Something'],
                    ],
                ],
            ],
        ]);
    }

    public function testTitleContains(): void
    {
        $matches = Song::factory()->count(3)->create(['title' => 'Foo Something']);
        Song::factory()->count(3)->create(['title' => 'Foo Nothing']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'title',
                        'operator' => 'contains',
                        'value' => ['Some'],
                    ],
                ],
            ],
        ]);
    }

    public function testTitleDoesNotContain(): void
    {
        $matches = Song::factory()->count(3)->create(['title' => 'Foo Something']);
        Song::factory()->count(3)->create(['title' => 'Foo Nothing']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'title',
                        'operator' => 'notContain',
                        'value' => ['Nothing'],
                    ],
                ],
            ],
        ]);
    }

    public function testTitleBeginsWith(): void
    {
        $matches = Song::factory()->count(3)->create(['title' => 'Foo Something']);
        Song::factory()->count(3)->create(['title' => 'Bar Something']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'title',
                        'operator' => 'beginsWith',
                        'value' => ['Foo'],
                    ],
                ],
            ],
        ]);
    }

    public function testTitleEndsWith(): void
    {
        $matches = Song::factory()->count(3)->create(['title' => 'Foo Something']);
        Song::factory()->count(3)->create(['title' => 'Foo Nothing']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'title',
                        'operator' => 'endsWith',
                        'value' => ['Something'],
                    ],
                ],
            ],
        ]);
    }

    public function testAlbumIs(): void
    {
        $albums = Album::factory()->count(2)->create(['name' => 'Foo Album']);

        $matches = Song::factory()->count(3)->for($albums[0])->create()
            ->merge(Song::factory()->count(2)->for($albums[1])->create());

        Song::factory()->count(3)->create();

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'album.name',
                        'operator' => 'is',
                        'value' => ['Foo Album'],
                    ],
                ],
            ],
        ]);
    }

    public function testArtistIs(): void
    {
        $matches = Song::factory()
            ->count(3)
            ->for(Artist::factory()->create(['name' => 'Foo Artist']))
            ->create();

        Song::factory()->count(3)->create();

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'artist.name',
                        'operator' => 'is',
                        'value' => ['Foo Artist'],
                    ],
                ],
            ],
        ]);
    }

    public function testGenreIsOrContains(): void
    {
        $matches = Song::factory()->count(3)->create(['genre' => 'Foo Genre'])
            ->merge(Song::factory()->count(2)->create(['genre' => 'Bar Genre']));

        Song::factory()->count(3)->create(['genre' => 'Baz Genre']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'genre',
                        'operator' => 'is',
                        'value' => ['Foo Genre'],
                    ],
                ],
            ],
            [
                'id' => 2,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'genre',
                        'operator' => 'contains',
                        'value' => ['Bar'],
                    ],
                ],
            ],
        ]);
    }

    public function testYearIsGreaterThan(): void
    {
        $matches = Song::factory()->count(3)->create(['year' => 2030])
            ->merge(Song::factory()->count(2)->create(['year' => 2022]));

        Song::factory()->count(3)->create(['year' => 2020]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'year',
                        'operator' => 'isGreaterThan',
                        'value' => [2021],
                    ],
                ],
            ],
        ]);
    }

    public function testYearIsLessThan(): void
    {
        $matches = Song::factory()->count(3)->create(['year' => 1980])
            ->merge(Song::factory()->count(2)->create(['year' => 1978]));

        Song::factory()->count(3)->create(['year' => 1991]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'year',
                        'operator' => 'isLessThan',
                        'value' => [1981],
                    ],
                ],
            ],
        ]);
    }

    public function testYearIsBetween(): void
    {
        $matches = Song::factory()->count(3)->create(['year' => 1980])
            ->merge(Song::factory()->count(2)->create(['year' => 1978]));

        Song::factory()->count(3)->create(['year' => 1991]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'year',
                        'operator' => 'isBetween',
                        'value' => [1970, 1985],
                    ],
                ],
            ],
        ]);
    }

    public function testPlayCountIsGreaterThan(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $matches = Song::factory()->count(2)->create();

        $notMatch = Song::factory()->create();

        Interaction::factory()
            ->for($matches[0])
            ->for($user)
            ->create(['play_count' => 1000]);

        Interaction::factory()
            ->for($matches[1])
            ->for($user)
            ->create(['play_count' => 2000]);

        Interaction::factory()
            ->for($user)
            ->for($notMatch)
            ->create(['play_count' => 500]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'interactions.play_count',
                        'operator' => 'isGreaterThan',
                        'value' => [999],
                    ],
                ],
            ],
        ], $user);
    }

    public function testLastPlayedAtIsInLast(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $matches = Song::factory()->count(2)->create();

        $notMatch = Song::factory()->create();

        Interaction::factory()
            ->for($matches[0])
            ->for($user)
            ->create(['last_played_at' => now()->subDays(2)]);

        Interaction::factory()
            ->for($matches[1])
            ->for($user)
            ->create(['last_played_at' => now()->subDay()]);

        Interaction::factory()
            ->for($user)
            ->for($notMatch)
            ->count(2)
            ->create(['last_played_at' => now()->subDays(4)]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'interactions.last_played_at',
                        'operator' => 'inLast',
                        'value' => [3],
                    ],
                ],
            ],
        ], $user);
    }

    public function testLastPlayedNotInLast(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $matches = Song::factory()->count(2)->create();

        $notMatch = Song::factory()->create();

        Interaction::factory()
            ->for($matches[0])
            ->for($user)
            ->create(['last_played_at' => now()->subDays(4)]);

        Interaction::factory()
            ->for($matches[1])
            ->for($user)
            ->create(['last_played_at' => now()->subDays(3)]);

        Interaction::factory()
            ->for($user)
            ->for($notMatch)
            ->count(2)
            ->create(['last_played_at' => now()->subDays(2)]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'interactions.last_played_at',
                        'operator' => 'notInLast',
                        'value' => [2],
                    ],
                ],
            ],
        ], $user);
    }

    public function testLastPlayedIs(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $matches = Song::factory()->count(2)->create();

        $notMatch = Song::factory()->create();

        Interaction::factory()
            ->for($matches[0])
            ->for($user)
            ->create(['last_played_at' => now()]);

        Interaction::factory()
            ->for($matches[1])
            ->for($user)
            ->create(['last_played_at' => now()]);

        Interaction::factory()
            ->for($user)
            ->for($notMatch)
            ->count(2)
            ->create(['last_played_at' => now()->subDays(4)]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'interactions.last_played_at',
                        'operator' => 'is',
                        'value' => [now()->format('Y-m-d')],
                    ],
                ],
            ],
        ], $user);
    }

    public function testLengthIsGreaterThan(): void
    {
        $matches = Song::factory()->count(3)->create(['length' => 300])
            ->merge(Song::factory()->count(2)->create(['length' => 200]));

        Song::factory()->count(3)->create(['length' => 100]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'length',
                        'operator' => 'isGreaterThan',
                        'value' => [199],
                    ],
                ],
            ],
        ]);
    }

    public function testLengthIsInBetween(): void
    {
        $matches = Song::factory()->count(3)->create(['length' => 300])
            ->merge(Song::factory()->count(2)->create(['length' => 200]));

        Song::factory()->count(3)->create(['length' => 100]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'length',
                        'operator' => 'isBetween',
                        'value' => [199, 301],
                    ],
                ],
            ],
        ]);
    }

    public function testDateAddedInLast(): void
    {
        $matches = Song::factory()->count(3)->create(['created_at' => now()->subDays(2)])
            ->merge(Song::factory()->count(2)->create(['created_at' => now()->subDay()]))
            ->merge(Song::factory()->count(1)->create(['created_at' => today()]));

        Song::factory()->count(3)->create(['created_at' => now()->subDays(4)]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'created_at',
                        'operator' => 'inLast',
                        'value' => [3],
                    ],
                ],
            ],
        ]);
    }

    public function testDateAddedNotInLast(): void
    {
        $matches = Song::factory()->count(3)->create(['created_at' => now()->subDays(4)])
            ->merge(Song::factory()->count(2)->create(['created_at' => now()->subDays(5)]))
            ->merge(Song::factory()->count(1)->create(['created_at' => now()->subDays(6)]));

        Song::factory()->count(3)->create(['created_at' => now()->subDays(2)]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 1,
                'rules' => [
                    [
                        'id' => 1,
                        'model' => 'created_at',
                        'operator' => 'notInLast',
                        'value' => [3],
                    ],
                ],
            ],
        ]);
    }

    private function assertMatchesAgainstRules(Collection $matches, array $rules, ?User $playlistOwner = null): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()
            ->for($playlistOwner ?? User::factory()->create())
            ->create(['rules' => $rules]);

        self::assertEqualsCanonicalizing(
            $matches->pluck('id')->all(),
            $this->service->getSongs($playlist)->pluck('id')->all()
        );
    }
}
