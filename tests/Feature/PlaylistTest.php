<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Values\SmartPlaylistRule;
use Illuminate\Support\Collection;

class PlaylistTest extends TestCase
{
    private const JSON_STRUCTURE = [
        'type',
        'id',
        'name',
        'folder_id',
        'user_id',
        'is_smart',
        'rules',
        'created_at',
    ];

    public function testListing(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        Playlist::factory()->for($user)->count(3)->create();

        $this->getAs('api/playlists', $user)
            ->assertJsonStructure(['*' => self::JSON_STRUCTURE])
            ->assertJsonCount(3, '*');
    }

    public function testCreatingPlaylist(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var array<Song>|Collection $songs */
        $songs = Song::factory(4)->create();

        $this->postAs('api/playlists', [
            'name' => 'Foo Bar',
            'songs' => $songs->pluck('id')->all(),
            'rules' => [],
        ], $user)
            ->assertJsonStructure(self::JSON_STRUCTURE);

        /** @var Playlist $playlist */
        $playlist = Playlist::query()->orderByDesc('id')->first();

        self::assertSame('Foo Bar', $playlist->name);
        self::assertTrue($playlist->user->is($user));
        self::assertNull($playlist->folder_id);
        self::assertEqualsCanonicalizing($songs->pluck('id')->all(), $playlist->songs->pluck('id')->all());
    }

    public function testCreatingSmartPlaylist(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $rule = SmartPlaylistRule::make([
            'model' => 'artist.name',
            'operator' => SmartPlaylistRule::OPERATOR_IS,
            'value' => ['Bob Dylan'],
        ]);

        $this->postAs('api/playlists', [
            'name' => 'Smart Foo Bar',
            'rules' => [
                [
                    'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
                    'rules' => [$rule->toArray()],
                ],
            ],
        ], $user)->assertJsonStructure(self::JSON_STRUCTURE);

        /** @var Playlist $playlist */
        $playlist = Playlist::query()->orderByDesc('id')->first();

        self::assertSame('Smart Foo Bar', $playlist->name);
        self::assertTrue($playlist->user->is($user));
        self::assertTrue($playlist->is_smart);
        self::assertCount(1, $playlist->rule_groups);
        self::assertNull($playlist->folder_id);
        self::assertTrue($rule->equals($playlist->rule_groups[0]->rules[0]));
    }

    public function testCreatingSmartPlaylistFailsIfSongsProvided(): void
    {
        $this->postAs('api/playlists', [
            'name' => 'Smart Foo Bar',
            'rules' => [
                [
                    'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
                    'rules' => [
                        SmartPlaylistRule::make([
                            'model' => 'artist.name',
                            'operator' => SmartPlaylistRule::OPERATOR_IS,
                            'value' => ['Bob Dylan'],
                        ])->toArray(),
                    ],
                ],
            ],
            'songs' => Song::factory(3)->create()->pluck('id')->all(),
        ])->assertUnprocessable();
    }

    public function testCreatingPlaylistWithNonExistentSongsFails(): void
    {
        $this->postAs('api/playlists', [
            'name' => 'Foo Bar',
            'rules' => [],
            'songs' => ['foo'],
        ])
            ->assertUnprocessable();
    }

    public function testUpdatePlaylistName(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create(['name' => 'Foo']);

        $this->putAs("api/playlists/$playlist->id", ['name' => 'Bar'], $playlist->user)
            ->assertJsonStructure(self::JSON_STRUCTURE);

        self::assertSame('Bar', $playlist->refresh()->name);
    }

    public function testNonOwnerCannotUpdatePlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create(['name' => 'Foo']);

        $this->putAs("api/playlists/$playlist->id", ['name' => 'Qux'])->assertForbidden();
        self::assertSame('Foo', $playlist->refresh()->name);
    }

    public function testDeletePlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();

        $this->deleteAs("api/playlists/$playlist->id", [], $playlist->user);

        self::assertModelMissing($playlist);
    }

    public function testNonOwnerCannotDeletePlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();

        $this->deleteAs("api/playlists/$playlist->id")->assertForbidden();

        self::assertModelExists($playlist);
    }
}
