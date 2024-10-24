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
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;

class SmartPlaylistServiceTest extends TestCase
{
    private SmartPlaylistService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(SmartPlaylistService::class);
    }

    #[Test]
    public function titleIs(): void
    {
        $matches = Song::factory()->count(3)->create(['title' => 'Foo Something']);
        Song::factory()->count(3)->create(['title' => 'Bar Something']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'title',
                        'operator' => 'is',
                        'value' => ['Foo Something'],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function titleIsNot(): void
    {
        $matches = Song::factory()->count(3)->create(['title' => 'Foo Something']);
        Song::factory()->count(3)->create(['title' => 'Bar Something']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'title',
                        'operator' => 'isNot',
                        'value' => ['Bar Something'],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function titleContains(): void
    {
        $matches = Song::factory()->count(3)->create(['title' => 'Foo Something']);
        Song::factory()->count(3)->create(['title' => 'Foo Nothing']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'title',
                        'operator' => 'contains',
                        'value' => ['Some'],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function titleDoesNotContain(): void
    {
        $matches = Song::factory()->count(3)->create(['title' => 'Foo Something']);
        Song::factory()->count(3)->create(['title' => 'Foo Nothing']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'title',
                        'operator' => 'notContain',
                        'value' => ['Nothing'],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function titleBeginsWith(): void
    {
        $matches = Song::factory()->count(3)->create(['title' => 'Foo Something']);
        Song::factory()->count(3)->create(['title' => 'Bar Something']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'title',
                        'operator' => 'beginsWith',
                        'value' => ['Foo'],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function titleEndsWith(): void
    {
        $matches = Song::factory()->count(3)->create(['title' => 'Foo Something']);
        Song::factory()->count(3)->create(['title' => 'Foo Nothing']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'title',
                        'operator' => 'endsWith',
                        'value' => ['Something'],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function albumIs(): void
    {
        $albums = Album::factory()->count(2)->create(['name' => 'Foo Album']);

        $matches = Song::factory()->count(3)->for($albums[0])->create()
            ->merge(Song::factory()->count(2)->for($albums[1])->create());

        Song::factory()->count(3)->create();

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'album.name',
                        'operator' => 'is',
                        'value' => ['Foo Album'],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function artistIs(): void
    {
        $matches = Song::factory()
            ->count(3)
            ->for(Artist::factory()->create(['name' => 'Foo Artist']))
            ->create();

        Song::factory()->count(3)->create();

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'artist.name',
                        'operator' => 'is',
                        'value' => ['Foo Artist'],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function genreIsOrContains(): void
    {
        $matches = Song::factory()->count(3)->create(['genre' => 'Foo Genre'])
            ->merge(Song::factory()->count(2)->create(['genre' => 'Bar Genre']));

        Song::factory()->count(3)->create(['genre' => 'Baz Genre']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'genre',
                        'operator' => 'is',
                        'value' => ['Foo Genre'],
                    ],
                ],
            ],
            [
                'id' => '70fe0cbd-c0e3-4ce2-806b-30153795bdeb',
                'rules' => [
                    [
                        'id' => '50f1e8c1-170b-46a0-b752-d515440b34d9',
                        'model' => 'genre',
                        'operator' => 'contains',
                        'value' => ['Bar'],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function yearIsGreaterThan(): void
    {
        $matches = Song::factory()->count(3)->create(['year' => 2030])
            ->merge(Song::factory()->count(2)->create(['year' => 2022]));

        Song::factory()->count(3)->create(['year' => 2020]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'year',
                        'operator' => 'isGreaterThan',
                        'value' => [2021],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function yearIsLessThan(): void
    {
        $matches = Song::factory()->count(3)->create(['year' => 1980])
            ->merge(Song::factory()->count(2)->create(['year' => 1978]));

        Song::factory()->count(3)->create(['year' => 1991]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'year',
                        'operator' => 'isLessThan',
                        'value' => [1981],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function yearIsBetween(): void
    {
        $matches = Song::factory()->count(3)->create(['year' => 1980])
            ->merge(Song::factory()->count(2)->create(['year' => 1978]));

        Song::factory()->count(3)->create(['year' => 1991]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'year',
                        'operator' => 'isBetween',
                        'value' => [1970, 1985],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function playCountIsGreaterThan(): void
    {
        $user = create_user();
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
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'interactions.play_count',
                        'operator' => 'isGreaterThan',
                        'value' => [999],
                    ],
                ],
            ],
        ], $user);
    }

    #[Test]
    public function lastPlayedAtIsInLast(): void
    {
        $user = create_user();
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
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'interactions.last_played_at',
                        'operator' => 'inLast',
                        'value' => [3],
                    ],
                ],
            ],
        ], $user);
    }

    #[Test]
    public function lastPlayedNotInLast(): void
    {
        $user = create_user();
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
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'interactions.last_played_at',
                        'operator' => 'notInLast',
                        'value' => [2],
                    ],
                ],
            ],
        ], $user);
    }

    #[Test]
    public function lastPlayedIs(): void
    {
        $user = create_user();
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
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'interactions.last_played_at',
                        'operator' => 'is',
                        'value' => [now()->format('Y-m-d')],
                    ],
                ],
            ],
        ], $user);
    }

    #[Test]
    public function lengthIsGreaterThan(): void
    {
        $matches = Song::factory()->count(3)->create(['length' => 300])
            ->merge(Song::factory()->count(2)->create(['length' => 200]));

        Song::factory()->count(3)->create(['length' => 100]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'length',
                        'operator' => 'isGreaterThan',
                        'value' => [199],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function lengthIsInBetween(): void
    {
        $matches = Song::factory()->count(3)->create(['length' => 300])
            ->merge(Song::factory()->count(2)->create(['length' => 200]));

        Song::factory()->count(3)->create(['length' => 100]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'length',
                        'operator' => 'isBetween',
                        'value' => [199, 301],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function dateAddedInLast(): void
    {
        $matches = Song::factory()->count(3)->create(['created_at' => now()->subDays(2)])
            ->merge(Song::factory()->count(2)->create(['created_at' => now()->subDay()]))
            ->merge(Song::factory()->count(1)->create(['created_at' => today()]));

        Song::factory()->count(3)->create(['created_at' => now()->subDays(4)]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'created_at',
                        'operator' => 'inLast',
                        'value' => [3],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function dateAddedNotInLast(): void
    {
        $matches = Song::factory()->count(3)->create(['created_at' => now()->subDays(4)])
            ->merge(Song::factory()->count(2)->create(['created_at' => now()->subDays(5)]))
            ->merge(Song::factory()->count(1)->create(['created_at' => now()->subDays(6)]));

        Song::factory()->count(3)->create(['created_at' => now()->subDays(2)]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                'rules' => [
                    [
                        'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                        'model' => 'created_at',
                        'operator' => 'notInLast',
                        'value' => [3],
                    ],
                ],
            ],
        ]);
    }

    protected function assertMatchesAgainstRules(
        Collection $matches,
        array $rules,
        ?User $owner = null,
    ): void {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->for($owner ?? create_admin())->create(['rules' => $rules]);

        self::assertEqualsCanonicalizing(
            $matches->pluck('id')->all(),
            $this->service->getSongs($playlist)->pluck('id')->all()
        );
    }
}
