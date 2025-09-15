<?php

namespace Tests\Integration\Services;

use App\Helpers\Uuid;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Interaction;
use App\Models\Song;
use App\Models\User;
use App\Services\SmartPlaylistService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_playlist;
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
        $matches = Song::factory()->count(1)->create(['title' => 'Foo Something']);
        Song::factory()->create(['title' => 'Bar Something']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
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
        $matches = Song::factory()->count(1)->create(['title' => 'Foo Something']);
        Song::factory()->create(['title' => 'Bar Something']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
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
        $matches = Song::factory()->count(1)->create(['title' => 'Foo Something']);
        Song::factory()->create(['title' => 'Foo Nothing']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
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
        $matches = Song::factory()->count(1)->create(['title' => 'Foo Something']);
        Song::factory()->create(['title' => 'Foo Nothing']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
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
        $matches = Song::factory()->count(1)->create(['title' => 'Foo Something']);
        Song::factory()->create(['title' => 'Bar Something']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
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
        $matches = Song::factory()->count(1)->create(['title' => 'Foo Something']);
        Song::factory()->create(['title' => 'Foo Nothing']);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
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
        $album = Album::factory()->create(['name' => 'Foo Album']);
        $matches = Song::factory()->count(1)->for($album)->create();
        Song::factory()->create();

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
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
            ->count(1)
            ->for(Artist::factory()->create(['name' => 'Foo Artist']))
            ->create([
                'artist_name' => 'Foo Artist',
            ]);

        Song::factory()->create();

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
                        'model' => 'artist.name',
                        'operator' => 'is',
                        'value' => ['Foo Artist'],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function genreIs(): void
    {
        $genre = Genre::factory()->create(['name' => 'Foo Genre']);
        $matches = Song::factory()->count(1)->hasAttached($genre)->create();

        Song::factory()->create();

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
                        'model' => 'genre',
                        'operator' => 'is',
                        'value' => ['Foo Genre'],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function genreIsNot(): void
    {
        $genre = Genre::factory()->create(['name' => 'Foo Genre']);
        $matches = Song::factory()->count(1)->create();

        Song::factory()->hasAttached($genre)->create();

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
                        'model' => 'genre',
                        'operator' => 'isNot',
                        'value' => ['Foo Genre'],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function yearIsGreaterThan(): void
    {
        $matches = Song::factory()->count(1)->create(['year' => 2030])
            ->merge(Song::factory()->count(1)->create(['year' => 2022]));

        Song::factory()->create(['year' => 2020]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
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
        $matches = Song::factory()->count(1)->create(['year' => 1980])
            ->merge(Song::factory()->count(1)->create(['year' => 1978]));

        Song::factory()->create(['year' => 1991]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
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
        $matches = Song::factory()->count(1)->create(['year' => 1980])
            ->merge(Song::factory()->count(1)->create(['year' => 1978]));

        Song::factory()->create(['year' => 1991]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
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
        $matches = Song::factory()->count(1)->create();
        $notMatch = Song::factory()->create();

        Interaction::factory()
            ->for($matches[0])
            ->for($user)
            ->create(['play_count' => 1000]);

        Interaction::factory()
            ->for($user)
            ->for($notMatch)
            ->create(['play_count' => 500]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
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
        $matches = Song::factory()->count(1)->create();
        $notMatch = Song::factory()->create();

        Interaction::factory()
            ->for($matches[0])
            ->for($user)
            ->create(['last_played_at' => now()->subDays(2)]);

        Interaction::factory()
            ->for($user)
            ->for($notMatch)
            ->create(['last_played_at' => now()->subDays(4)]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
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
        $matches = Song::factory()->count(1)->create();
        $notMatch = Song::factory()->create();

        Interaction::factory()
            ->for($matches[0])
            ->for($user)
            ->create(['last_played_at' => now()->subDays(3)]);

        Interaction::factory()
            ->for($user)
            ->for($notMatch)
            ->create(['last_played_at' => now()->subDays(2)]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
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
        $matches = Song::factory()->count(1)->create();
        $notMatch = Song::factory()->create();

        Interaction::factory()
            ->for($matches[0])
            ->for($user)
            ->create(['last_played_at' => now()]);

        Interaction::factory()
            ->for($user)
            ->for($notMatch)
            ->create(['last_played_at' => now()->subDays(4)]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
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
        $matches = Song::factory()->count(1)->create(['length' => 300])
            ->merge(Song::factory()->count(1)->create(['length' => 200]));

        Song::factory()->count(1)->create(['length' => 100]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
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
        $matches = Song::factory()->count(1)->create(['length' => 300])
            ->merge(Song::factory()->count(1)->create(['length' => 200]));

        Song::factory()->count(1)->create(['length' => 100]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
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
        $matches = Song::factory()->count(1)->create(['created_at' => now()->subDay()])
            ->merge(Song::factory()->count(1)->create(['created_at' => today()]));

        Song::factory()->count(1)->create(['created_at' => now()->subDays(4)]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
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
        $matches = Song::factory()->count(1)->create(['created_at' => now()->subDays(4)])
            ->merge(Song::factory()->count(1)->create(['created_at' => now()->subDays(5)]));

        Song::factory()->create(['created_at' => now()->subDays(2)]);

        $this->assertMatchesAgainstRules($matches, [
            [
                'id' => Uuid::generate(),
                'rules' => [
                    [
                        'id' => Uuid::generate(),
                        'model' => 'created_at',
                        'operator' => 'notInLast',
                        'value' => [3],
                    ],
                ],
            ],
        ]);
    }

    protected function assertMatchesAgainstRules(Collection $matches, array $rules, ?User $owner = null): void
    {
        $playlist = create_playlist(['rules' => $rules]);

        if ($owner) {
            $playlist->users()->detach();
            $playlist->users()->attach($owner, ['role' => 'owner']);
        }

        self::assertEqualsCanonicalizing(
            $matches->modelKeys(),
            $this->service->getSongs($playlist, $playlist->owner)->modelKeys()
        );
    }
}
